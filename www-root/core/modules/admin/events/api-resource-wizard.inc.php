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
 * API to handle interaction with the event resource wizard.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
    add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request_var = "_".$request;

    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));

    if (!$ERROR) {
        switch ($request) {
            case "POST" :
                switch ($method) {
                    case "add" :
                        if (isset(${$request_var}["upload"]) && $tmp_input = clean_input(${$request_var}["upload"], array("trim", "alpha"))) {
                            $PROCESSED["upload"] = "upload";
                        }

                        if (isset(${$request_var}["event_id"]) && $tmp_input = clean_input(${$request_var}["event_id"], array("trim", "int"))) {
                            $PROCESSED["event_id"] = $tmp_input;
                        } else {
                            add_error("Invalid event ID supplied.");
                        }

                        $recurring_events = 0;
                        if (isset(${$request_var}["recurring_event_ids"]) && is_string(${$request_var}["recurring_event_ids"]) && !empty(${$request_var}["recurring_event_ids"])) {
                            $tmp = json_decode(${$request_var}["recurring_event_ids"]);
                            if (isset($tmp) && is_string($tmp)) {
                                $PROCESSED["recurring_event_ids"] = json_decode($tmp);
                            }

                            if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"])) {
                                $recurring_events = 1;
                            }
                        }

                        if (isset(${$request_var}["resource_recurring_event_ids"]) && is_string(${$request_var}["resource_recurring_event_ids"]) && !empty(${$request_var}["resource_recurring_event_ids"])) {
                            //resource_recurring_event_ids
                            $PROCESSED["recurring_event_ids"] = explode(",", ${$request_var}["resource_recurring_event_ids"]);

                            if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"])) {
                                $recurring_events = 1;
                            }
                        }

                        if (isset(${$request_var}["event_resource_entity_id"]) && $tmp_input = clean_input(${$request_var}["event_resource_entity_id"], array("trim", "int"))) {
                            $PROCESSED["event_resource_entity_id"] = $tmp_input;
                        }

                        if (isset(${$request_var}["step"]) && $tmp_input = clean_input(${$request_var}["step"], array("trim", "int"))) {
                            $step = $tmp_input;
                        } else {
                            add_error("Invalid step provided.");
                        }

                        if (isset(${$request_var}["resource_substep"]) && $tmp_input = clean_input(${$request_var}["resource_substep"], array("trim", "int"))) {
                            $PROCESSED["substep"] = $tmp_input;
                        } else {
                            add_error("Invalid sub step provided.");
                        }

                        if (isset(${$request_var}["resource_id"]) && $tmp_input = clean_input(${$request_var}["resource_id"], array("trim", "int"))) {
                            $PROCESSED["resource_id"] = $tmp_input;
                        }
                        switch ($step) {
                            case 1 :
                                if (isset(${$request_var}["event_resource_type_value"]) && $tmp_input = clean_input($_POST["event_resource_type_value"], array("trim", "int"))) {
                                    $PROCESSED["event_resource_type_value"] = $tmp_input;
                                } else {
                                    add_error("Please select a resource type to Add to this event.");
                                }

                                if (!$ERROR) {
                                    $PROCESSED["next_step"] = 2;
                                    echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }

                                break;
                            case 2 :
                                if (isset(${$request_var}["event_resource_required_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_required_value"], array("trim", "alpha"))) {
                                    $PROCESSED["event_resource_required_value"] = $tmp_input;
                                } else {
                                    add_error("Please select an option to indicate whether this resource is optional or required.");
                                }

                                if (isset(${$request_var}["event_resource_timeframe_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_timeframe_value"], array("trim", "alpha"))) {
                                    $PROCESSED["event_resource_timeframe_value"] = $tmp_input;
                                } else {
                                    add_error("Please select an option to indicate when this resource should be used by the learner.");
                                }

                                if (!$ERROR) {
                                    $default_dates = array();
                                    $event = Models_Event::get($PROCESSED["event_id"]);
                                    if ($event) {
                                        switch ($PROCESSED["event_resource_timeframe_value"]) {
                                            case "none" :
                                                $default_dates["release_start"] = "";
                                                $default_dates["release_until"] = "";
                                                $default_dates["release_start_time"] = "";
                                                $default_dates["release_until_time"] = "";
                                                break;
                                            case "pre" :
                                                $default_dates["release_start"] = "";
                                                $default_dates["release_until"] = date("Y-m-d", $event->getEventFinish());
                                                $default_dates["release_start_time"] = "";
                                                $default_dates["release_until_time"] = date("H:i", $event->getEventFinish());
                                                break;
                                            case "during" :
                                                $default_dates["release_start"] = date("Y-m-d", $event->getEventStart());
                                                $default_dates["release_until"] = date("Y-m-d", $event->getEventFinish());
                                                $default_dates["release_start_time"] = date("H:i", $event->getEventStart());
                                                $default_dates["release_until_time"] = date("H:i", $event->getEventFinish());
                                                break;
                                            case "post" :
                                                $default_dates["release_start"] = date("Y-m-d", $event->getEventStart());
                                                $default_dates["release_until"] = "";
                                                $default_dates["release_start_time"] = date("H:i", $event->getEventStart());
                                                $default_dates["release_until_time"] = "";
                                                break;
                                        }
                                    }

                                    $PROCESSED["next_step"] = 3;
                                    echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "default_dates" => $default_dates)));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                                break;
                            case 3 :

                                /**
                                 *  Time release options
                                 *
                                 */
                                if (isset(${$request_var}["event_resource_release_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_value"], array("trim", "alpha"))) {
                                    $PROCESSED["event_resource_release_value"] = $tmp_input;

                                    switch ($tmp_input) {
                                        case "yes" :
                                            $PROCESSED["required"] = 1;
                                            break;
                                        case "no" :
                                            $PROCESSED["required"] = 0;
                                            break;
                                    }

                                } else {
                                    add_error("Please select a time release option for this resource");
                                }

                                if (!$ERROR) {
                                    switch ($PROCESSED["event_resource_release_value"]) {
                                        case "yes" :
                                            if (isset(${$request_var}["event_resource_release_start_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_start_value"], array("trim", "striptags"))) {
                                                $PROCESSED["start_date"] = $tmp_input;
                                            } else {
                                                $PROCESSED["start_date"] = 0;
                                            }

                                            if (isset(${$request_var}["event_resource_release_start_time_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_start_time_value"], array("trim", "striptags"))) {
                                                $PROCESSED["start_time"] = $tmp_input;
                                            } else {
                                                $PROCESSED["start_time"] = "00:00";
                                            }

                                            if (isset(${$request_var}["event_resource_release_finish_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_finish_value"], array("trim", "striptags"))) {
                                                $PROCESSED["finish_date"] = $tmp_input;
                                            } else {
                                                $PROCESSED["finish_date"] = 0;
                                            }

                                            if (isset(${$request_var}["event_resource_release_finish_time_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_finish_time_value"], array("trim", "striptags"))) {
                                                $PROCESSED["finish_time"] = $tmp_input;
                                            } else {
                                                $PROCESSED["finish_time"] = "00:00";
                                            }

                                            if (!$PROCESSED["start_date"] && !$PROCESSED["finish_date"]) {
                                                add_error("Please provide either a Start Date or End Date for this resource");
                                            }

                                            if (!$ERROR) {
                                                $release_start = strtotime($PROCESSED["start_date"] . " " . $PROCESSED["start_time"]);
                                                $release_finish = strtotime($PROCESSED["finish_date"] . " " . $PROCESSED["finish_time"]);

                                                if ($release_start && $release_finish) {
                                                    if ($release_finish < $release_start) {
                                                        add_error("The finish date must come after the start date");
                                                    }
                                                }

                                                if (!$ERROR) {
                                                    //if it's a recurring event, next is 4, otherwise set it to 5
                                                    ($recurring_events ? $PROCESSED["next_step"] = 4 : $PROCESSED["next_step"] = 5);
                                                    echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1)));
                                                } else {
                                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                }

                                            } else {
                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                            }
                                            break;
                                        case "no" :
                                            ($recurring_events ? $PROCESSED["next_step"] = 4 : $PROCESSED["next_step"] = 5);
                                            echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1)));
                                            break;
                                    }
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                                break;
                            case 4 :
                                if (isset($recurring_events) && $recurring_events === true) {
                                    $PROCESSED["next_step"] = 5;
                                    echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1)));
                                } else {
                                    $PROCESSED["next_step"] = 5;
                                    echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1)));
                                }
                                break;
                            case 5 :
                                if (isset(${$request_var}["event_resource_type_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_type_value"], array("trim", "int"))) {
                                    $PROCESSED["event_resource_type_value"] = $tmp_input;
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }

                                if (isset(${$request_var}["event_resource_required_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_required_value"], array("trim", "alpha"))) {
                                    switch ($tmp_input) {
                                        case "yes" :
                                            $PROCESSED["required"] = 1;
                                            break;
                                        case "no" :
                                            $PROCESSED["required"] = 0;
                                            break;
                                    }
                                } else {
                                    add_error("Please select an option to indicate whether this resource is optional or required.");
                                }

                                if (isset(${$request_var}["event_resource_timeframe_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_timeframe_value"], array("trim", "alpha"))) {
                                    $PROCESSED["timeframe"] = $tmp_input;
                                } else {
                                    add_error("Please select an option to indicate when this resource should be used by the learner.");
                                }

                                if (isset(${$request_var}["event_resource_release_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_value"], array("trim", "alpha"))) {
                                    switch ($tmp_input) {
                                        case "yes" :
                                            $PROCESSED["release_required"] = 1;
                                            break;
                                        case "no" :
                                            $PROCESSED["release_required"] = 0;
                                            break;
                                    }
                                } else {
                                    add_error("Please select a time release option for this resource");
                                }

                                if (isset($PROCESSED["release_required"])) {
                                    switch ($PROCESSED["release_required"]) {
                                        case 1 :
                                            if (isset(${$request_var}["event_resource_release_start_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_start_value"], array("trim", "striptags"))) {
                                                $PROCESSED["start_date"] = $tmp_input;
                                            } else {
                                                $PROCESSED["start_date"] = "0";
                                            }

                                            if (isset(${$request_var}["event_resource_release_start_time_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_start_time_value"], array("trim", "striptags"))) {
                                                $PROCESSED["start_time"] = $tmp_input;
                                            } else {
                                                $PROCESSED["start_time"] = "00:00";
                                            }

                                            if (isset(${$request_var}["event_resource_release_finish_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_finish_value"], array("trim", "striptags"))) {
                                                $PROCESSED["finish_date"] = $tmp_input;
                                            } else {
                                                $PROCESSED["finish_date"] = "0";
                                            }

                                            if (isset(${$request_var}["event_resource_release_finish_time_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_release_finish_time_value"], array("trim", "striptags"))) {
                                                $PROCESSED["finish_time"] = $tmp_input;
                                            } else {
                                                $PROCESSED["finish_time"] = "00:00";
                                            }

                                            $PROCESSED["release_date"] = strtotime($PROCESSED["start_date"] . " " . $PROCESSED["start_time"]);
                                            $PROCESSED["release_until"] = strtotime($PROCESSED["finish_date"] . " " . $PROCESSED["finish_time"]);

                                            if ($PROCESSED["release_date"] && $PROCESSED["release_until"]) {
                                                if ($PROCESSED["release_until"] < $PROCESSED["release_date"]) {
                                                    add_error("The finish date must come after the start date");
                                                }
                                            }
                                            break;
                                        case 0 :
                                            $PROCESSED["release_date"] = 0;
                                            $PROCESSED["release_until"] = 0;
                                            break;
                                    }
                                }

                                $PROCESSED["updated_date"] = time();
                                $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                                $PROCESSED["active"] = 1;
                                if (!$ERROR) {
                                    switch ($PROCESSED["event_resource_type_value"]) {
                                        case 2:
                                            if (isset(${$request_var}["event_resource_bring_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_bring_description_value"], array("trim", "striptags"))) {
                                                $PROCESSED["resource_class_work"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a description for this resource");
                                            }

                                            if (!$ERROR) {
                                                if (isset($PROCESSED["resource_id"])) {
                                                    $PROCESSED["event_resource_class_work_id"] = $PROCESSED["resource_id"];
                                                    $method = "update";
                                                    $current_resource           = Models_Event_Resource_Classwork::fetchRowByID($PROCESSED["event_resource_class_work_id"]);
                                                    $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                    $current_resource_date      = $current_resource->getUpdatedDate();
                                                    $current_entity_type        = $current_resource_entity->getEntityType();
                                                } else {
                                                    $method = "insert";
                                                }

                                                $resource_class_work = new Models_Event_Resource_Classwork($PROCESSED);

                                                if ($resource_class_work->$method()) {
                                                    if (isset($PROCESSED["event_resource_entity_id"])) {
                                                        $PROCESSED_ENTITY["event_resource_entity_id"] = $PROCESSED["event_resource_entity_id"];
                                                    }

                                                    $PROCESSED_ENTITY["event_id"] = $PROCESSED["event_id"];
                                                    $PROCESSED_ENTITY["entity_type"] = $PROCESSED["event_resource_type_value"];
                                                    $PROCESSED_ENTITY["entity_value"] = $resource_class_work->getID();
                                                    $PROCESSED_ENTITY["release_date"] = $PROCESSED["release_date"];
                                                    $PROCESSED_ENTITY["release_until"] = $PROCESSED["release_until"];
                                                    $PROCESSED_ENTITY["updated_date"] = time();
                                                    $PROCESSED_ENTITY["updated_by"] = $ENTRADA_USER->getActiveID();
                                                    $PROCESSED_ENTITY["active"] = 1;

                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                    if (!$resource_entity->$method()) {
                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                        application_log("error", "Failed to " .$method. " Classwork event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    }
                                                } else {
                                                    add_error("An error occured while attempting to save this resource. Pleas try again later.");
                                                    application_log("error", "Failed to " .$method. " Classwork event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                }
                                            }


                                            if (!$ERROR) {

                                                last_updated("event", $PROCESSED["event_id"]);

                                                if ($method == "insert") {
                                                    history_log($PROCESSED["event_id"], "added a classwork resource.", $ENTRADA_USER->getID());
                                                } else {
                                                    history_log($PROCESSED["event_id"], "updated a classwork resource.", $ENTRADA_USER->getID());
                                                }

                                                $PROCESSED["next_step"] = 6;
                                                application_log("success", "Successfully added Event Resource ". $resource_class_work->getID()  ." to event " . $PROCESSED["event_id"]);
                                                //now we update the other recurring events
                                                if (isset($recurring_events) && $recurring_events == 1) {
                                                    if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                        foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                            if (isset($current_resource) && is_object($current_resource)) {
                                                                $resource = $current_resource->getResourceClasswork();
                                                                $resource_recurring = Models_Event_Resource_Classwork::fetchRowByEventIdResourceUpdate($r_event_id, $resource, $current_resource_date);

                                                                if (isset($resource_recurring) && is_object($resource_recurring)) {
                                                                    $resource_recurring_id = $resource_recurring->getID();

                                                                    $resource_recurring_updated = new Models_Event_Resource_Classwork(array(
                                                                        "event_resource_class_work_id" => $resource_recurring_id,
                                                                        "event_id"              => $r_event_id,
                                                                        "resource_class_work"   => $PROCESSED["resource_class_work"],
                                                                        "required"              => $PROCESSED["required"],
                                                                        "timeframe"             => $PROCESSED["timeframe"],
                                                                        "release_date"          => $PROCESSED["release_date"],
                                                                        "release_until"         => $PROCESSED["release_until"],
                                                                        "updated_date"          => $PROCESSED["updated_date"],
                                                                        "updated_by"            => $PROCESSED["updated_by"]
                                                                    ));

                                                                    if ($resource_recurring_updated->update()) {
                                                                        //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                        $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_updated->getID());

                                                                        if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                            $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                            $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                            $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_updated->getID();
                                                                            $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                            $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                            if (!$resource_entity->update()) {
                                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                application_log("error", "Failed to " . $method . " classwork event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                            } else {
                                                                                //log success\
                                                                                history_log($r_event_id, "updated classwork.", $ENTRADA_USER->getID());
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            } else {
                                                                //insert recurring
                                                                $recurring_event = $PROCESSED;
                                                                $recurring_event["event_id"] = $r_event_id;
                                                                $resource = new Models_Event_Resource_Classwork($recurring_event);

                                                                if ($resource->insert()) {
                                                                    last_updated("event", $recurring_event["event_id"]);
                                                                    //insert

                                                                    $resource_recurring_id = $resource->getID();

                                                                    $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                    $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                    $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_id;
                                                                    $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                    $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                    $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                    $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                    $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                    if (!$resource_entity->insert()) {
                                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                        application_log("error", "Failed to " . $method . " classwork event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                    } else {
                                                                        //log success
                                                                        history_log($r_event_id, "inserted classwork.", $ENTRADA_USER->getID());
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                if (!$ERROR) {
                                                    echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                } else {
                                                    application_log("error", "Failed to " .$method. " classwork event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                }
                                            } else {
                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                            }

                                            break;
                                        case 4:
                                            if (isset(${$request_var}["event_resource_homework_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_homework_description_value"], array("trim", "striptags"))) {
                                                $PROCESSED["resource_homework"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a description for this resource");
                                            }

                                            if (!$ERROR) {
                                                if (isset($PROCESSED["resource_id"])) {
                                                    $PROCESSED["event_resource_homework_id"] = $PROCESSED["resource_id"];
                                                    $method = "update";
                                                    $current_resource           = Models_Event_Resource_Homework::fetchRowByID($PROCESSED["event_resource_homework_id"]);
                                                    $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                    $current_resource_date      = $current_resource->getUpdatedDate();
                                                    $current_entity_type        = $current_resource_entity->getEntityType();
                                                } else {
                                                    $method = "insert";
                                                }

                                                $resource_homework = new Models_Event_Resource_Homework($PROCESSED);
                                                if ($resource_homework->$method()) {

                                                    if (isset($PROCESSED["event_resource_entity_id"])) {
                                                        $PROCESSED_ENTITY["event_resource_entity_id"] = $PROCESSED["event_resource_entity_id"];
                                                    }

                                                    $PROCESSED_ENTITY["event_id"] = $PROCESSED["event_id"];
                                                    $PROCESSED_ENTITY["entity_type"] = $PROCESSED["event_resource_type_value"];
                                                    $PROCESSED_ENTITY["entity_value"] = $resource_homework->getID();
                                                    $PROCESSED_ENTITY["release_date"] = $PROCESSED["release_date"];
                                                    $PROCESSED_ENTITY["release_until"] = $PROCESSED["release_until"];
                                                    $PROCESSED_ENTITY["updated_date"] = time();
                                                    $PROCESSED_ENTITY["updated_by"] = $ENTRADA_USER->getActiveID();
                                                    $PROCESSED_ENTITY["active"] = 1;

                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                    if (!$resource_entity->$method()) {
                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                        application_log("error", "Failed to " .$method. " Homework event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    }

                                                    if (!$ERROR) {
                                                        last_updated("event", $PROCESSED["event_id"]);

                                                        if ($method == "insert") {
                                                            history_log($PROCESSED["event_id"], "added a homework resource.", $ENTRADA_USER->getID());
                                                        } else {
                                                            history_log($PROCESSED["event_id"], "updated a homework resource.", $ENTRADA_USER->getID());
                                                        }

                                                        $PROCESSED["next_step"] = 6;
                                                        application_log("success", "Successfully added Event Resource ". $resource_homework->getID()  ." to event " . $PROCESSED["event_id"]);
                                                        //now we update the other recurring events
                                                        if (isset($recurring_events) && $recurring_events == 1) {
                                                            if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                    if (isset($current_resource) && is_object($current_resource)) {
                                                                        $resource = $current_resource->getResourceHomework();
                                                                        $resource_recurring = Models_Event_Resource_Homework::fetchRowByEventIdResourceUpdate($r_event_id, $resource, $current_resource_date);

                                                                        if (isset($resource_recurring) && is_object($resource_recurring)) {
                                                                            $resource_recurring_id = $resource_recurring->getID();

                                                                            $resource_recurring_updated = new Models_Event_Resource_Homework(array(
                                                                                "event_resource_homework_id" => $resource_recurring_id,
                                                                                "event_id"          => $r_event_id,
                                                                                "resource_homework" => $PROCESSED["resource_homework"],
                                                                                "required"          => $PROCESSED["required"],
                                                                                "timeframe"         => $PROCESSED["timeframe"],
                                                                                "release_date"      => $PROCESSED["release_date"],
                                                                                "release_until"     => $PROCESSED["release_until"],
                                                                                "updated_date"      => $PROCESSED["updated_date"],
                                                                                "updated_by"        => $PROCESSED["updated_by"]
                                                                            ));

                                                                            if ($resource_recurring_updated->update()) {
                                                                                //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                                $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_updated->getID());

                                                                                if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                                    $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_updated->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                    $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                    if (!$resource_entity->update()) {
                                                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                        application_log("error", "Failed to " . $method . " Homework event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                    } else {
                                                                                        //log success\
                                                                                        history_log($r_event_id, "updated Homework.", $ENTRADA_USER->getID());
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    } else {
                                                                        //insert recurring
                                                                        $recurring_event = $PROCESSED;
                                                                        $recurring_event["event_id"] = $r_event_id;
                                                                        $resource = new Models_Event_Resource_Homework($recurring_event);

                                                                        if ($resource->insert()) {
                                                                            last_updated("event", $recurring_event["event_id"]);
                                                                            //insert

                                                                            $resource_recurring_id = $resource->getID();

                                                                            $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_id;
                                                                            $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                            $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                            if (!$resource_entity->insert()) {
                                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                application_log("error", "Failed to " . $method . " Homework event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                            } else {
                                                                                //log success
                                                                                history_log($r_event_id, "inserted Homework.", $ENTRADA_USER->getID());
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        if (!$ERROR) {
                                                            echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                        } else {
                                                            application_log("error", "Failed to " .$method. " Homework event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                        }
                                                    }
                                                } else {
                                                    add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                    application_log("error", "Failed to " .$method. " Homework event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                }
                                            } else {
                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                            }
                                            break;
                                        case 9:
                                            if (isset(${$request_var}["event_resource_textbook_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_textbook_description_value"], array("trim", "striptags"))) {
                                                $PROCESSED["resource_textbook_reading"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a description for this resource");
                                            }

                                            if (!$ERROR) {
                                                if (isset($PROCESSED["resource_id"])) {
                                                    $PROCESSED["event_resource_textbook_reading_id"] = $PROCESSED["resource_id"];
                                                    $method = "update";
                                                    $current_resource           = Models_Event_Resource_TextbookReading::fetchRowByID($PROCESSED["event_resource_textbook_reading_id"]);
                                                    $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                    $current_resource_date      = $current_resource->getUpdatedDate();
                                                    $current_entity_type        = $current_resource_entity->getEntityType();
                                                } else {
                                                    $method = "insert";
                                                }

                                                $resource_textbook_reading = new Models_Event_Resource_TextbookReading($PROCESSED);
                                                if ($resource_textbook_reading->$method()) {

                                                    if (isset($PROCESSED["event_resource_entity_id"])) {
                                                        $PROCESSED_ENTITY["event_resource_entity_id"] = $PROCESSED["event_resource_entity_id"];
                                                    }

                                                    $PROCESSED_ENTITY["event_id"] = $PROCESSED["event_id"];
                                                    $PROCESSED_ENTITY["entity_type"] = $PROCESSED["event_resource_type_value"];
                                                    $PROCESSED_ENTITY["entity_value"] = $resource_textbook_reading->getID();
                                                    $PROCESSED_ENTITY["release_date"] = $PROCESSED["release_date"];
                                                    $PROCESSED_ENTITY["release_until"] = $PROCESSED["release_until"];
                                                    $PROCESSED_ENTITY["updated_date"] = time();
                                                    $PROCESSED_ENTITY["updated_by"] = $ENTRADA_USER->getActiveID();
                                                    $PROCESSED_ENTITY["active"] = 1;

                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                    if (!$resource_entity->$method()) {
                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                        application_log("error", "Failed to " .$method. " Textbook reading event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    }

                                                    if (!$ERROR) {
                                                        last_updated("event", $PROCESSED["event_id"]);

                                                        if ($method == "insert") {
                                                            history_log($PROCESSED["event_id"], "added a textbook reading resource.", $ENTRADA_USER->getID());
                                                        } else {
                                                            history_log($PROCESSED["event_id"], "updated a textbook reading resource.", $ENTRADA_USER->getID());
                                                        }

                                                        $PROCESSED["next_step"] = 6;
                                                        application_log("success", "Successfully added Event Resource ". $resource_textbook_reading->getID()  ." to event " . $PROCESSED["event_id"]);

                                                        //now we update the other recurring events
                                                        if (isset($recurring_events) && $recurring_events == 1) {
                                                            if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                    if (isset($current_resource) && is_object($current_resource)) {
                                                                        $resource = $current_resource->getResourceTextbookReading();
                                                                        $resource_recurring = Models_Event_Resource_TextbookReading::fetchRowByEventIdResourceUpdate($r_event_id, $resource, $current_resource_date);

                                                                        if (isset($resource_recurring) && is_object($resource_recurring)) {
                                                                            $resource_recurring_id = $resource_recurring->getID();

                                                                            $resource_recurring_updated = new Models_Event_Resource_TextbookReading(array(
                                                                                "event_resource_textbook_reading_id" => $resource_recurring_id,                                                                                "event_resource_homework_id" => $resource_recurring_id,
                                                                                "event_id"          => $r_event_id,
                                                                                "resource_textbook_reading" => $PROCESSED["resource_textbook_reading"],
                                                                                "required"          => $PROCESSED["required"],
                                                                                "timeframe"         => $PROCESSED["timeframe"],
                                                                                "release_date"      => $PROCESSED["release_date"],
                                                                                "release_until"     => $PROCESSED["release_until"],
                                                                                "updated_date"      => $PROCESSED["updated_date"],
                                                                                "updated_by"        => $PROCESSED["updated_by"]
                                                                            ));

                                                                            if ($resource_recurring_updated->update()) {
                                                                                //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                                $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_updated->getID());

                                                                                if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                                    $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_updated->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                    $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                    if (!$resource_entity->update()) {
                                                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                        application_log("error", "Failed to " . $method . " Textbook reading event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                    } else {
                                                                                        //log success\
                                                                                        history_log($r_event_id, "updated Textbook reading.", $ENTRADA_USER->getID());
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    } else {
                                                                        //insert recurring
                                                                        $recurring_event = $PROCESSED;
                                                                        $recurring_event["event_id"] = $r_event_id;
                                                                        $resource = new Models_Event_Resource_TextbookReading($recurring_event);

                                                                        if ($resource->insert()) {
                                                                            last_updated("event", $recurring_event["event_id"]);
                                                                            //insert

                                                                            $resource_recurring_id = $resource->getID();

                                                                            $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_id;
                                                                            $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                            $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                            if (!$resource_entity->insert()) {
                                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                application_log("error", "Failed to " . $method . " Textbook reading event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                            } else {
                                                                                //log success
                                                                                history_log($r_event_id, "inserted Textbook reading.", $ENTRADA_USER->getID());
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        if (!$ERROR) {
                                                            echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                        } else {
                                                            application_log("error", "Failed to " .$method. " Textbook reading event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                        }
                                                    }
                                                } else {
                                                    application_log("error", "Failed to " .$method. " Textbook reading event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                }
                                            } else {
                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                            }
                                            break;
                                        case 1 :
                                        case 5 :
                                        case 6 :
                                        case 11 :
                                            /**
                                             * File upload (3 steps)
                                             */

                                            switch ($PROCESSED["substep"]) {
                                                case 1 :
                                                    if (isset($PROCESSED["resource_id"])) {
                                                        if (isset(${$request_var}["event_resource_attach_file"]) && $tmp_input = clean_input(${$request_var}["event_resource_attach_file"], array("trim", "alpha"))) {
                                                            $PROCESSED["event_resource_attach_file"] = $tmp_input;
                                                        } else {
                                                            add_error("Please indicate if you would like to replace the existing file");
                                                        }
                                                    }

                                                    if (isset(${$request_var}["event_resource_file_view_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_file_view_value"], array("trim", "alpha"))) {
                                                        switch ($tmp_input) {
                                                            case "view" :
                                                                $PROCESSED["access_method"] = 1;
                                                                break;
                                                            case "download" :
                                                                $PROCESSED["access_method"] = 0;
                                                                break;
                                                        }
                                                    } else {
                                                        add_error("Please indicate if this file should be downloaded or viewed directly in the browser");
                                                    }

                                                    if (isset($PROCESSED["resource_id"])) {
                                                        if (isset(${$request_var}["event_resource_file_title_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_file_title_value"], array("trim", "striptags"))) {
                                                            $PROCESSED["file_title"] = $tmp_input;
                                                        } else {
                                                            add_error("Please provide a title for this resource");
                                                        }
                                                    } else {
                                                        $PROCESSED["file_title"] = "";
                                                    }

                                                    if (isset(${$request_var}["event_resource_file_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_file_description_value"], array("trim", "striptags"))) {
                                                        $PROCESSED["file_notes"] = $tmp_input;
                                                    } else {
                                                        add_error("Please provide a description for this resource");
                                                    }

                                                    if (!$ERROR) {
                                                        if (isset($PROCESSED["event_resource_attach_file"]) && $PROCESSED["event_resource_attach_file"] == "no") {
                                                            $PROCESSED["efile_id"] = $PROCESSED["resource_id"];

                                                            $rf = Models_Event_Resource_File::fetchRowByID($PROCESSED["resource_id"]);
                                                            $current_resource_file = $rf;
                                                            if ($rf) {
                                                                $PROCESSED["file_category"] = $rf->getFileCategory();
                                                                $PROCESSED["file_type"] = $rf->getFileType();
                                                                $PROCESSED["file_size"] = $rf->getFileSize();
                                                                $PROCESSED["file_name"] = $rf->getFileName();
                                                            }

                                                            switch ($PROCESSED["event_resource_type_value"]) {
                                                                case 1 :
                                                                    $file_type = "podcast";
                                                                    break;
                                                                case 5 :
                                                                    $file_type = "lecture notes";
                                                                    break;
                                                                case 6 :
                                                                    $file_type = "lecture slides";
                                                                    break;
                                                                case 11 :
                                                                    $file_type = "other";
                                                                    break;
                                                            }

                                                            $resource_file = new Models_Event_Resource_File($PROCESSED);
                                                            if ($resource_file->update()) {
                                                                last_updated("event", $PROCESSED["event_id"]);

                                                                if ($method == "insert") {
                                                                    history_log($PROCESSED["event_id"], "added " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());
                                                                } else {
                                                                    history_log($PROCESSED["event_id"], "updated " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());

                                                                    if (isset($recurring_events) && $recurring_events == 1) {
                                                                        if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                            foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                                if (isset($current_resource_file) && is_object($current_resource_file)) {
                                                                                    $name = $current_resource_file->getFileName();
                                                                                    $resource_recurring_file = Models_Event_Resource_File::fetchRowByEventIDName($r_event_id, $name);

                                                                                    if (isset($resource_recurring_file) && is_object($resource_recurring_file)) {
                                                                                        $resource_recurring_file_id = $resource_recurring_file->getID();

                                                                                        $resource_recurring_file_updated = new Models_Event_Resource_File(array(
                                                                                            "efile_id"      => $resource_recurring_file_id,
                                                                                            "event_id"      => $r_event_id,
                                                                                            "required"      => $PROCESSED["required"],
                                                                                            "timeframe"     => $PROCESSED["timeframe"],
                                                                                            "file_category" => $PROCESSED["file_category"],
                                                                                            "file_type"     => $PROCESSED["file_type"],
                                                                                            "file_size"     => $PROCESSED["file_size"],
                                                                                            "file_name"     => $PROCESSED["file_name"],
                                                                                            "file_title"    => $PROCESSED["file_title"],
                                                                                            "file_notes"    => $PROCESSED["file_notes"],
                                                                                            "access_method" => $PROCESSED["access_method"],
                                                                                            "release_date"  => $PROCESSED["release_date"],
                                                                                            "release_until" => $PROCESSED["release_until"],
                                                                                            "updated_date"  => $PROCESSED["updated_date"],
                                                                                            "updated_by"    => $PROCESSED["updated_by"]
                                                                                        ));

                                                                                        if ($resource_recurring_file_updated->update()) {
                                                                                            history_log($r_event_id, "updated " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  . " file.", $ENTRADA_USER->getID());
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }

                                                                application_log("success", "Successfully updated Event Resource ". $resource_file->getID()  ." to event " . $PROCESSED["event_id"]);
                                                                echo json_encode(array("status" => "success", "data" => array("next_step" => 6, "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                            } else {
                                                                add_error("A problem occured while attempting to update this event resource. Please try again later.");
                                                                application_log("error", "Failed to update File event resource entity " . $PROCESSED["efile_id"] . " for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                            }
                                                        } else {
                                                            echo json_encode(array("status" => "success", "data" => array("next_step" => 5, "sub_step" => 2, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                        }
                                                    } else {
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }
                                                    break;
                                                case 2 :
                                                    if (!$ERROR) {
                                                        echo json_encode(array("status" => "success", "data" => array("next_step" => 5, "sub_step" => 3, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                    } else {
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }
                                                    break;
                                                case 3 :

                                                    if (isset($_FILES["file"]["name"])) {
                                                        switch ($_FILES["file"]["error"]) {
                                                            case 0 :
                                                                $PROCESSED["file_size"] = (int) trim($_FILES["file"]["size"]);
                                                                $PROCESSED["file_name"] = useable_filename(trim($_FILES["file"]["name"]));
                                                                $PROCESSED["file_type"] = trim($_FILES["file"]["type"]);

                                                                if ($PROCESSED["event_resource_type_value"] == "1") {
                                                                    if (!in_array($PROCESSED["file_type"], $VALID_PODCASTS)) {
                                                                        add_error("The provided file was not a valid Podcast file.");
                                                                    }
                                                                }
                                                                break;
                                                            case 1 :
                                                            case 2 :
                                                                add_error("The uploaded file exceeds the allowed file size limit.");
                                                                break;
                                                            case 3 :
                                                                add_error("The file that uploaded did not complete the upload process or was interupted. Please try again.");
                                                                break;
                                                            case 4 :
                                                                add_error("You did not select a file on your computer to upload. Please select a local file.");
                                                                break;
                                                            case 5 :
                                                                add_error("A problem occured while attempting to upload the file; the MEdTech Unit has been informed of this error, please try again later.");
                                                                break;
                                                            case 6 :
                                                            case 7 :
                                                                add_error("Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.");
                                                                break;
                                                        }
                                                    } else {
                                                        add_error("You did not select a file on your computer to upload. Please select a local file.");
                                                    }

                                                    if (isset(${$request_var}["event_resource_file_view_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_file_view_value"], array("trim", "alpha"))) {
                                                        switch ($tmp_input) {
                                                            case "view" :
                                                                $PROCESSED["access_method"] = 1;
                                                                break;
                                                            case "download" :
                                                                $PROCESSED["access_method"] = 0;
                                                                break;
                                                        }
                                                    } else {
                                                        add_error("Please indicate if this file should be downloaded or viewed directly in the browser");
                                                    }

                                                    $PROCESSED["accesses"] = 0;
                                                    $PROCESSED["file_category"] = "other";
                                                    $file_type = "";

                                                    switch ($PROCESSED["event_resource_type_value"]) {
                                                        case 1 :
                                                            $PROCESSED["file_category"] = "podcast";
                                                            $file_type = "podcast";
                                                            break;
                                                        case 5 :
                                                            $PROCESSED["file_category"] = "lecture_notes";
                                                            $file_type = "lecture notes";
                                                            break;
                                                        case 6 :
                                                            $PROCESSED["file_category"] = "lecture_slides";
                                                            $file_type = "lecture slides";
                                                            break;
                                                        case 11 :
                                                            $PROCESSED["file_category"] = "other";
                                                            $file_type = "other";
                                                            break;
                                                    }

                                                    if (isset($PROCESSED["resource_id"])) {
                                                        if (isset(${$request_var}["event_resource_file_title_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_file_title_value"], array("trim", "striptags"))) {
                                                            $PROCESSED["file_title"] = $tmp_input;
                                                        } else {
                                                            add_error("Please provide a title for this resource");
                                                        }

                                                        if (isset(${$request_var}["event_resource_attach_file"]) && $tmp_input = clean_input(${$request_var}["event_resource_attach_file"], array("trim", "alpha"))) {
                                                            $PROCESSED["event_resource_attach_file"] = $tmp_input;
                                                        } else {
                                                            add_error("Please indicate if you would like to replace the existing file.");
                                                        }

                                                    } else {
                                                        $PROCESSED["file_title"] = "";
                                                    }

                                                    if (isset(${$request_var}["event_resource_file_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_file_description_value"], array("trim", "striptags"))) {
                                                        $PROCESSED["file_notes"] = $tmp_input;
                                                    } else {
                                                        add_error("Please provide a description for this resource");
                                                    }

                                                    if (!$ERROR) {
                                                        if (isset($PROCESSED["resource_id"])) {
                                                            $PROCESSED["efile_id"] = $PROCESSED["resource_id"];
                                                            $current_resource_file      = Models_Event_Resource_File::fetchRowByID($PROCESSED["efile_id"]);
                                                            $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                            $current_entity_type        = $current_resource_entity->getEntityType();
                                                            $method = "update";
                                                        } else {
                                                            $method = "insert";
                                                        }

                                                        $resource_file = new Models_Event_Resource_File($PROCESSED);
                                                        if ($resource_file->$method()) {
                                                            last_updated("event", $PROCESSED["event_id"]);

                                                            $EFILE_ID = $resource_file->getID();
                                                            if (isset($PROCESSED["event_resource_attach_file"]) && $PROCESSED["event_resource_attach_file"] == "yes") {

                                                            }

                                                            if ((@is_dir(FILE_STORAGE_PATH)) && (@is_writable(FILE_STORAGE_PATH))) {
                                                                if (@file_exists(FILE_STORAGE_PATH."/".$EFILE_ID)) {
                                                                    application_log("notice", "File ID [".$EFILE_ID."] already existed and was overwritten with newer file.");
                                                                }

                                                                if (@move_uploaded_file($_FILES["file"]["tmp_name"], FILE_STORAGE_PATH."/".$EFILE_ID)) {
                                                                    application_log("success", "File ID ".$EFILE_ID." was successfully added to the database and filesystem for event [".$PROCESSED["event_id"]."].");
                                                                } else {
                                                                    add_error("The new file was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.");
                                                                    application_log("error", "The move_uploaded_file function failed to move temporary file over to final location.");
                                                                }
                                                            }

                                                            if (isset($PROCESSED["event_resource_entity_id"])) {
                                                                $PROCESSED_ENTITY["event_resource_entity_id"] = $PROCESSED["event_resource_entity_id"];
                                                            }

                                                            $PROCESSED_ENTITY["event_id"] = $PROCESSED["event_id"];
                                                            $PROCESSED_ENTITY["entity_type"] = $PROCESSED["event_resource_type_value"];
                                                            $PROCESSED_ENTITY["entity_value"] = $resource_file->getID();
                                                            $PROCESSED_ENTITY["release_date"] = $PROCESSED["release_date"];
                                                            $PROCESSED_ENTITY["release_until"] = $PROCESSED["release_until"];
                                                            $PROCESSED_ENTITY["updated_date"] = time();
                                                            $PROCESSED_ENTITY["updated_by"] = $ENTRADA_USER->getActiveID();
                                                            $PROCESSED_ENTITY["active"] = 1;

                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                            if (!$resource_entity->$method()) {
                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                application_log("error", "Failed to " . $method . " File event resource entity for event: " . $PROCESSED["event_id"] . " DB said:" . $db->ErrorMsg());
                                                            }

                                                            if (!$ERROR) {
                                                                last_updated("event", $PROCESSED["event_id"]);
                                                                if (isset($PROCESSED["upload"])) {
                                                                    if ($method == "insert") {
                                                                        history_log($PROCESSED["event_id"], "added " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());
                                                                    } else {
                                                                        history_log($PROCESSED["event_id"], "updated " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());
                                                                    }

                                                                    add_success("Successfully Saved file.");
                                                                    header("Location: ".ENTRADA_URL . "/admin/events?section=content&id=" .$PROCESSED["event_id"]);
                                                                } else {
                                                                    if ($method == "insert") {
                                                                        history_log($PROCESSED["event_id"], "added " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());
                                                                    } else {
                                                                        history_log($PROCESSED["event_id"], "updated " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());
                                                                    }

                                                                    $PROCESSED["next_step"] = 6;
                                                                    application_log("success", "Successfully added Event Resource ". $resource_file->getID()  ." to event " . $PROCESSED["event_id"]);
                                                                    //now we update the other recurring events
                                                                    if (isset($recurring_events) && $recurring_events == 1) {

                                                                        // if method is update get the current resource file  Models_Event_Resource_File
                                                                        // by $PROCESSED["efile_id"]:
                                                                        // we can then use this to update with fetchRowByEventIDName
                                                                        // from that we get the $resource_entity from fetchRowByEventIDEntityValue with
                                                                        // EntityValue being the Models_Event_Resource_File primary id
                                                                        if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                            foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                                if (isset($current_resource_file) && is_object($current_resource_file)) {
                                                                                    $name = $current_resource_file->getFileName();
                                                                                    $resource_recurring_file = Models_Event_Resource_File::fetchRowByEventIDName($r_event_id, $name);

                                                                                    if (isset($resource_recurring_file) && is_object($resource_recurring_file)) {
                                                                                        $resource_recurring_file_id = $resource_recurring_file->getID();

                                                                                        if ((@is_dir(FILE_STORAGE_PATH)) && (@is_writable(FILE_STORAGE_PATH))) {
                                                                                            if (@file_exists(FILE_STORAGE_PATH . "/" . $resource_recurring_file_id)) {
                                                                                                application_log("notice", "File ID [" . $resource_recurring_file_id . "] already existed and was overwritten with newer file.");
                                                                                            }

                                                                                            if (@file_exists(FILE_STORAGE_PATH . "/" . $EFILE_ID)) {
                                                                                                copy(FILE_STORAGE_PATH . "/" . $EFILE_ID, FILE_STORAGE_PATH . "/" . $resource_recurring_file_id);
                                                                                                application_log("success", "File ID " . $resource_recurring_file_id . " was successfully added to the database and filesystem for event [" . $r_event_id . "].");
                                                                                            } else {
                                                                                                add_error("The new file was not successfully saved. The Support Unit has been informed of this error, please try again later.");
                                                                                                application_log("error", "The move_uploaded_file function failed to move temporary file over to final location.");
                                                                                            }
                                                                                        }

                                                                                        $resource_recurring_file_updated = new Models_Event_Resource_File(array(
                                                                                            "efile_id"      => $resource_recurring_file_id,
                                                                                            "event_id"      => $r_event_id,
                                                                                            "required"      => $PROCESSED["required"],
                                                                                            "timeframe"     => $PROCESSED["timeframe"],
                                                                                            "file_category" => $PROCESSED["file_category"],
                                                                                            "file_type"     => $PROCESSED["file_type"],
                                                                                            "file_size"     => $PROCESSED["file_size"],
                                                                                            "file_name"     => $PROCESSED["file_name"],
                                                                                            "file_title"    => $PROCESSED["file_title"],
                                                                                            "file_notes"    => $PROCESSED["file_notes"],
                                                                                            "access_method" => $PROCESSED["access_method"],
                                                                                            "release_date"  => $PROCESSED["release_date"],
                                                                                            "release_until" => $PROCESSED["release_until"],
                                                                                            "updated_date"  => $PROCESSED["updated_date"],
                                                                                            "updated_by"    => $PROCESSED["updated_by"]
                                                                                        ));

                                                                                        if ($resource_recurring_file_updated->update()) {
                                                                                            //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                                            $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_file_updated->getID());

                                                                                            if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                                                $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                                                $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                                                $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                                $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_file_updated->getID();
                                                                                                $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                                $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                                $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                                $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                                $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                                $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                                if (!$resource_entity->update()) {
                                                                                                    add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                                    application_log("error", "Failed to " . $method . " File event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                                } else {
                                                                                                    //log success
                                                                                                    history_log($r_event_id, "updated " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                } else {
                                                                                    $recurring_event = $PROCESSED;
                                                                                    $recurring_event["event_id"] = $r_event_id;
                                                                                    $resource_file = new Models_Event_Resource_File($recurring_event);

                                                                                    if ($resource_file->insert()) {
                                                                                        last_updated("event", $recurring_event["event_id"]);
                                                                                        //insert

                                                                                        $resource_recurring_file_id = $resource_file->getID();

                                                                                        if ((@is_dir(FILE_STORAGE_PATH)) && (@is_writable(FILE_STORAGE_PATH))) {
                                                                                            if (@file_exists(FILE_STORAGE_PATH . "/" . $EFILE_ID)) {
                                                                                                application_log("notice", "File ID [" . $EFILE_ID . "] already existed and was overwritten with newer file.");
                                                                                            }

                                                                                            if (@file_exists(FILE_STORAGE_PATH . "/" . $EFILE_ID)) {
                                                                                                copy(FILE_STORAGE_PATH . "/" . $EFILE_ID, FILE_STORAGE_PATH . "/" . $resource_recurring_file_id);
                                                                                                application_log("success", "File ID " . $resource_recurring_file_id . " was successfully added to the database and filesystem for event [" . $r_event_id . "].");
                                                                                            } else {
                                                                                                add_error("The new file was not successfully saved. The Support Unit has been informed of this error, please try again later.");
                                                                                                application_log("error", "The move_uploaded_file function failed to move temporary file over to final location.");
                                                                                            }
                                                                                        }

                                                                                        $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                                        $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                        $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_file_id;
                                                                                        $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                        $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                        $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                        $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                        $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                        $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                        if (!$resource_entity->insert()) {
                                                                                            add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                            application_log("error", "Failed to " . $method . " File event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                        } else {
                                                                                            //log success
                                                                                            history_log($r_event_id, "inserted " . ($PROCESSED["file_title"]  == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " " . $file_type  ." file.", $ENTRADA_USER->getID());
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }

                                                                    if (!$ERROR) {
                                                                        echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 2, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                                    } else {
                                                                        application_log("error", "Failed to " .$method. " File event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                                    }
                                                                }
                                                            } else {
                                                                application_log("error", "Failed to " .$method. " File event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                            }
                                                        }
                                                    } else {
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }
                                                    break;
                                            }
                                            break;
                                        case 3 :
                                            /**
                                             * Link (3 steps)
                                             */

                                            if (isset(${$request_var}["event_resource_link_proxy_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_link_proxy_value"], array("trim", "alpha"))) {
                                                switch ($tmp_input) {
                                                    case "yes" :
                                                        $PROCESSED["proxify"] = 1;
                                                        break;
                                                    case "no" :
                                                        $PROCESSED["proxify"] = 0;
                                                        break;
                                                }
                                            } else {
                                                add_error("Please select an option indicating if this resource requires the proxy to be enabled");
                                            }

                                            if (isset(${$request_var}["event_resource_link_url_value"]) && ${$request_var}["event_resource_link_url_value"] != "http://" && $tmp_input = clean_input(${$request_var}["event_resource_link_url_value"], array("trim", "striptags"))) {
                                                $PROCESSED["link"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a valid URL for this resource");
                                            }

                                            if (isset(${$request_var}["event_resource_link_title_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_link_title_value"], array("trim", "striptags"))) {
                                                $PROCESSED["link_title"] = $tmp_input;
                                            } else {
                                                $PROCESSED["link_title"] = "";
                                            }

                                            if (isset(${$request_var}["event_resource_link_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_link_description_value"], array("trim", "striptags"))) {
                                                $PROCESSED["link_notes"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a description for this resource");
                                            }

                                            $PROCESSED["accesses"] = 0;

                                            if (!$ERROR) {
                                                if (isset($PROCESSED["resource_id"])) {
                                                    $PROCESSED["elink_id"]      = $PROCESSED["resource_id"];
                                                    $current_resource           = Models_Event_Resource_Link::fetchRowByID($PROCESSED["elink_id"]);
                                                    $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                    $current_resource_date      = $current_resource->getUpdatedDate();
                                                    $current_entity_type        = $current_resource_entity->getEntityType();
                                                    $method = "update";
                                                } else {
                                                    $method = "insert";
                                                }

                                                $resource_link = new Models_Event_Resource_Link($PROCESSED);
                                                if ($resource_link->$method()) {
                                                    if (isset($PROCESSED["event_resource_entity_id"])) {
                                                        $PROCESSED_ENTITY["event_resource_entity_id"] = $PROCESSED["event_resource_entity_id"];
                                                    }

                                                    $PROCESSED_ENTITY["event_id"]       = $PROCESSED["event_id"];
                                                    $PROCESSED_ENTITY["entity_type"]    = $PROCESSED["event_resource_type_value"];
                                                    $PROCESSED_ENTITY["entity_value"]   = $resource_link->getID();
                                                    $PROCESSED_ENTITY["release_date"]   = $PROCESSED["release_date"];
                                                    $PROCESSED_ENTITY["release_until"]  = $PROCESSED["release_until"];
                                                    $PROCESSED_ENTITY["updated_date"]   = time();
                                                    $PROCESSED_ENTITY["updated_by"]     = $ENTRADA_USER->getActiveID();
                                                    $PROCESSED_ENTITY["active"]         = 1;

                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                    if (!$resource_entity->$method()) {
                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                        application_log("error", "Failed to " .$method. " Link event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    }

                                                    if (!$ERROR) {
                                                        last_updated("event", $PROCESSED["event_id"]);

                                                        if ($method == "insert") {
                                                            history_log($PROCESSED["event_id"], "added $PROCESSED[link] link.", $ENTRADA_USER->getID());
                                                        } else {
                                                            history_log($PROCESSED["event_id"], "updated $PROCESSED[link_title] link.", $ENTRADA_USER->getID());
                                                        }

                                                        $PROCESSED["next_step"] = 6;
                                                        application_log("success", "Successfully added Event Resource ". $resource_link->getID()  ." to event " . $PROCESSED["event_id"]);

                                                        //now we update the other recurring events
                                                        if (isset($recurring_events) && $recurring_events == 1) {
                                                            if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                    if (isset($current_resource) && is_object($current_resource)) {
                                                                        $link = $current_resource->getLink();
                                                                        $resource_recurring = Models_Event_Resource_Link::fetchRowByEventIDLinkUpdate($r_event_id, $link, $current_resource_date);

                                                                        if (isset($resource_recurring) && is_object($resource_recurring)) {
                                                                            $resource_recurring_id = $resource_recurring->getID();

                                                                            $resource_recurring_updated = new Models_Event_Resource_Link(array(
                                                                                "elink_id"      => $resource_recurring_id,
                                                                                "event_id"      => $r_event_id,
                                                                                "required"      => $PROCESSED["required"],
                                                                                "timeframe"     => $PROCESSED["timeframe"],
                                                                                "proxify"       => $PROCESSED["proxify"],
                                                                                "link"          => $PROCESSED["link"],
                                                                                "link_title"    => $PROCESSED["link_title"],
                                                                                "link_notes"    => $PROCESSED["link_notes"],
                                                                                "accesses"      => $PROCESSED["accesses"],
                                                                                "release_date"  => $PROCESSED["release_date"],
                                                                                "release_until" => $PROCESSED["release_until"],
                                                                                "updated_date"  => $PROCESSED["updated_date"],
                                                                                "updated_by"    => $PROCESSED["updated_by"]
                                                                            ));

                                                                            if ($resource_recurring_updated->update()) {
                                                                                //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                                $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_updated->getID());

                                                                                if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                                    $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_updated->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                    $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                    if (!$resource_entity->update()) {
                                                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                        application_log("error", "Failed to " . $method . " Link event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                    } else {
                                                                                        //log success\
                                                                                        history_log($r_event_id, "updated " . $PROCESSED["link_title"] . "link.", $ENTRADA_USER->getID());
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    } else {
                                                                        //insert recurring
                                                                        $recurring_event = $PROCESSED;
                                                                        $recurring_event["event_id"] = $r_event_id;
                                                                        $resource = new Models_Event_Resource_Link($recurring_event);

                                                                        if ($resource->insert()) {
                                                                            last_updated("event", $recurring_event["event_id"]);
                                                                            //insert

                                                                            $resource_recurring_id = $resource->getID();

                                                                            $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_id;
                                                                            $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                            $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                            if (!$resource_entity->insert()) {
                                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                application_log("error", "Failed to " . $method . " File event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                            } else {
                                                                                //log success
                                                                                history_log($r_event_id, "inserted " . $PROCESSED["link_title"] . "link.", $ENTRADA_USER->getID());
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        if (!$ERROR) {
                                                            echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                        } else {
                                                            application_log("error", "Failed to " .$method. " Link event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                        }
                                                    } else {
                                                        application_log("error", "Failed to " .$method. " Link event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }
                                                } else {
                                                    add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                }
                                            } else {
                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                            }
                                            break;
                                        case 7 :

                                            /**
                                             * Module
                                             */

                                            if (isset(${$request_var}["event_resource_module_proxy_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_module_proxy_value"], array("trim", "alpha"))) {
                                                switch ($tmp_input) {
                                                    case "yes" :
                                                        $PROCESSED["proxify"] = 1;
                                                        break;
                                                    case "no" :
                                                        $PROCESSED["proxify"] = 0;
                                                        break;
                                                }
                                            } else {
                                                add_error("Please select an option indicating if this resource requires the proxy to be enabled");
                                            }

                                            if (isset(${$request_var}["event_resource_module_url_value"]) && ${$request_var}["event_resource_module_url_value"] != "http://" && $tmp_input = clean_input(${$request_var}["event_resource_module_url_value"], array("trim", "striptags"))) {
                                                $PROCESSED["link"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a valid URL for this resource");
                                            }

                                            if (isset(${$request_var}["event_resource_module_title_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_module_title_value"], array("trim", "striptags"))) {
                                                $PROCESSED["link_title"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a title for this resource");
                                            }

                                            if (isset(${$request_var}["event_resource_module_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_module_description_value"], array("trim", "striptags"))) {
                                                $PROCESSED["link_notes"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a description for this resource");
                                            }

                                            $PROCESSED["accesses"] = 0;

                                            if (!$ERROR) {
                                                if (isset($PROCESSED["resource_id"])) {
                                                    $PROCESSED["elink_id"] = $PROCESSED["resource_id"];
                                                    $method = "update";
                                                    $current_resource           = Models_Event_Resource_Link::fetchRowByID($PROCESSED["elink_id"]);
                                                    $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                    $current_resource_date      = $current_resource->getUpdatedDate();
                                                    $current_entity_type        = $current_resource_entity->getEntityType();
                                                } else {
                                                    $method = "insert";
                                                }

                                                $resource_link = new Models_Event_Resource_Link($PROCESSED);
                                                if ($resource_link->$method()) {
                                                    if (isset($PROCESSED["elink_id"])) {
                                                        $PROCESSED_ENTITY["elink_id"] = $PROCESSED["elink_id"];
                                                    }

                                                    $PROCESSED_ENTITY["event_id"] = $PROCESSED["event_id"];
                                                    $PROCESSED_ENTITY["entity_type"] = $PROCESSED["event_resource_type_value"];
                                                    $PROCESSED_ENTITY["entity_value"] = $resource_link->getID();
                                                    $PROCESSED_ENTITY["release_date"] = $PROCESSED["release_date"];
                                                    $PROCESSED_ENTITY["release_until"] = $PROCESSED["release_until"];
                                                    $PROCESSED_ENTITY["updated_date"] = time();
                                                    $PROCESSED_ENTITY["updated_by"] = $ENTRADA_USER->getActiveID();
                                                    $PROCESSED_ENTITY["active"] = 1;

                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                    if (!$resource_entity->$method()) {
                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                        application_log("error", "Failed to " .$method. " Link event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    }

                                                    if (!$ERROR) {
                                                        last_updated("event", $PROCESSED["event_id"]);

                                                        if ($method == "insert") {
                                                            history_log($PROCESSED["event_id"], "added $PROCESSED[link] learning module.", $ENTRADA_USER->getID());
                                                        } else {
                                                            history_log($PROCESSED["event_id"], "updated $PROCESSED[link_title] learning module.", $ENTRADA_USER->getID());
                                                        }

                                                        $PROCESSED["next_step"] = 6;
                                                        application_log("success", "Successfully added Event Resource ". $resource_link->getID()  ." to event " . $PROCESSED["event_id"]);

                                                        //now we update the other recurring events
                                                        if (isset($recurring_events) && $recurring_events == 1) {
                                                            if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                    if (isset($current_resource) && is_object($current_resource)) {
                                                                        $link = $current_resource->getLink();
                                                                        $resource_recurring = Models_Event_Resource_Link::fetchRowByEventIDLinkUpdate($r_event_id, $link, $current_resource_date);

                                                                        if (isset($resource_recurring) && is_object($resource_recurring)) {
                                                                            $resource_recurring_id = $resource_recurring->getID();

                                                                            $resource_recurring_updated = new Models_Event_Resource_Link(array(
                                                                                "elink_id"      => $resource_recurring_id,
                                                                                "event_id"      => $r_event_id,
                                                                                "required"      => $PROCESSED["required"],
                                                                                "timeframe"     => $PROCESSED["timeframe"],
                                                                                "proxify"       => $PROCESSED["proxify"],
                                                                                "link"          => $PROCESSED["link"],
                                                                                "link_title"    => $PROCESSED["link_title"],
                                                                                "link_notes"    => $PROCESSED["link_notes"],
                                                                                "accesses"      => $PROCESSED["accesses"],
                                                                                "release_date"  => $PROCESSED["release_date"],
                                                                                "release_until" => $PROCESSED["release_until"],
                                                                                "updated_date"  => $PROCESSED["updated_date"],
                                                                                "updated_by"    => $PROCESSED["updated_by"]
                                                                            ));

                                                                            if ($resource_recurring_updated->update()) {
                                                                                //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                                $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_updated->getID());

                                                                                if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                                    $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_updated->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                    $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                    if (!$resource_entity->update()) {
                                                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                        application_log("error", "Failed to " . $method . " Link event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                    } else {
                                                                                        //log success\
                                                                                        history_log($r_event_id, "updated " . $PROCESSED["link_title"] . "link.", $ENTRADA_USER->getID());
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    } else {
                                                                        //insert recurring
                                                                        $recurring_event = $PROCESSED;
                                                                        $recurring_event["event_id"] = $r_event_id;
                                                                        $resource = new Models_Event_Resource_Link($recurring_event);

                                                                        if ($resource->insert()) {
                                                                            last_updated("event", $recurring_event["event_id"]);
                                                                            //insert

                                                                            $resource_recurring_id = $resource->getID();

                                                                            $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_id;
                                                                            $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                            $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                            if (!$resource_entity->insert()) {
                                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                application_log("error", "Failed to " . $method . " File event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                            } else {
                                                                                //log success
                                                                                history_log($r_event_id, "inserted " . $PROCESSED["link_title"] . "link.", $ENTRADA_USER->getID());
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        if (!$ERROR) {
                                                            echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                        } else {
                                                            application_log("error", "Failed to " .$method. " Link event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                        }
                                                    } else {
                                                        application_log("error", "Failed to " .$method. " Link event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }
                                                } else {
                                                    add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                }
                                            } else {
                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                            }
                                            break;
                                        case 8 :

                                            /**
                                             * Quiz (4 steps)
                                             */

                                            switch ($PROCESSED["substep"]) {
                                                case 1 :
                                                    if (isset(${$request_var}["event_resource_quiz_id_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_id_value"], array("trim", "int"))) {
                                                        $PROCESSED["event_resource_quiz_id_value"] = $tmp_input;
                                                    } else {
                                                        add_error("Please select a quiz from the list");
                                                    }

                                                    if (!$ERROR) {
                                                        $substep = 2;
                                                        echo json_encode(array("status" => "success", "data" => array("sub_step" => $substep, "next_step" => 5, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                    } else {
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }
                                                    break;
                                                case 2 :
                                                    if (isset(${$request_var}["event_resource_quiz_title_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_title_value"], array("trim", "striptags"))) {
                                                        $PROCESSED["event_resource_quiz_title_value"] = $tmp_input;
                                                    } else {
                                                        add_error("Please Enter a quiz title");
                                                    }

                                                    if (isset(${$request_var}["event_resource_quiz_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_description_value"], array("trim", "striptags"))) {
                                                        $PROCESSED["event_resource_quiz_description_value"] = $tmp_input;
                                                    } else {
                                                        $PROCESSED["event_resource_quiz_description_value"] = "";
                                                    }

                                                    if (!$ERROR) {
                                                        $substep = 3;
                                                        echo json_encode(array("status" => "success", "data" => array("sub_step" => $substep, "next_step" => 5, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                    } else {
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }
                                                    break;
                                                case 3 :

                                                    $PROCESSED["content_type"] = "event";
                                                    $PROCESSED["content_id"] = $PROCESSED["event_id"];

                                                    if (isset(${$request_var}["event_resource_quiz_id_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_id_value"], array("trim", "int"))) {
                                                        $PROCESSED["quiz_id"] = $tmp_input;
                                                    } else {
                                                        add_error("Please select a quiz from the list");
                                                    }

                                                    if (isset(${$request_var}["event_resource_quiz_title_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_title_value"], array("trim", "striptags"))) {
                                                        $PROCESSED["quiz_title"] = $tmp_input;
                                                    } else {
                                                        $PROCESSED["quiz_title"] = "";
                                                    }

                                                    if (isset(${$request_var}["event_resource_quiz_instructions_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_instructions_value"], array("trim", "striptags"))) {
                                                        $PROCESSED["quiz_notes"] = $tmp_input;
                                                    } else {
                                                        $PROCESSED["quiz_notes"] = "";
                                                    }

                                                    if (isset(${$request_var}["event_resource_quiz_attendance_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_attendance_value"], array("trim", "alpha"))) {
                                                        switch ($tmp_input) {
                                                            case "yes" :
                                                                $PROCESSED["require_attendance"] = 1;
                                                                break;
                                                            case "no" :
                                                                $PROCESSED["require_attendance"] = 0;
                                                                break;
                                                        }
                                                    } else {
                                                        add_error("Please indicate if attendance is optional or required for this quiz.");
                                                    }

                                                    if (isset(${$request_var}["event_resource_quiz_shuffled_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_shuffled_value"], array("trim", "alpha"))) {
                                                        switch ($tmp_input) {
                                                            case "yes" :
                                                                $PROCESSED["random_order"] = 1;
                                                                break;
                                                            case "no" :
                                                                $PROCESSED["random_order"] = 0;
                                                                break;
                                                        }
                                                    } else {
                                                        add_error("Please indicate if the questions for this quiz should be shuffled or not.");
                                                    }

                                                    if (isset(${$request_var}["event_resource_quiz_time_value"])) {
                                                        $tmp_input = clean_input(${$request_var}["event_resource_quiz_time_value"], array("trim", "int"));
                                                        $PROCESSED["quiz_timeout"] = $tmp_input;
                                                    } else {
                                                        add_error("Please provide a time limit in minutes.");
                                                    }

                                                    if (isset(${$request_var}["event_resource_quiz_attempts_value"])) {
                                                        $tmp_input = clean_input(${$request_var}["event_resource_quiz_attempts_value"], array("trim", "int"));
                                                        $PROCESSED["quiz_attempts"] = $tmp_input;
                                                    } else {
                                                        add_error("Please provide a valid number of attempts for this quiz.");
                                                    }

                                                    $PROCESSED["accesses"] = 0;

                                                    if (isset(${$request_var}["event_resource_quiz_results_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_quiz_results_value"], array("trim", "alpha"))) {
                                                        switch ($tmp_input) {
                                                            case "immediate" :
                                                                $PROCESSED["quiztype_id"] = 2;
                                                                break;
                                                            case "delayed" :
                                                                $PROCESSED["quiztype_id"] = 1;
                                                                break;
                                                            case "hide" :
                                                                $PROCESSED["quiztype_id"] = 3;
                                                                break;
                                                        }
                                                    } else {
                                                        add_error("Please provide a valid number of attempts for this quiz.");
                                                    }

                                                    if (!$ERROR) {

                                                        if (isset($PROCESSED["resource_id"])) {
                                                            $PROCESSED["aquiz_id"] = $PROCESSED["resource_id"];
                                                            $method = "update";
                                                            $current_resource           = Models_Quiz_Attached::fetchRowByID($PROCESSED["aquiz_id"]);
                                                            $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                            $current_resource_date      = $current_resource->getUpdatedDate();
                                                            $current_entity_type        = $current_resource_entity->getEntityType();
                                                        } else {
                                                            $method = "insert";
                                                        }

                                                        $resource_quiz = new Models_Quiz_Attached($PROCESSED);
                                                        if ($resource_quiz->$method()) {

                                                            if (isset($PROCESSED["event_resource_entity_id"])) {
                                                                $PROCESSED_ENTITY["event_resource_entity_id"] = $PROCESSED["event_resource_entity_id"];
                                                            }

                                                            $PROCESSED_ENTITY["event_id"] = $PROCESSED["event_id"];
                                                            $PROCESSED_ENTITY["entity_type"] = $PROCESSED["event_resource_type_value"];
                                                            $PROCESSED_ENTITY["entity_value"] = $resource_quiz->getAquizID();
                                                            $PROCESSED_ENTITY["release_date"] = $PROCESSED["release_date"];
                                                            $PROCESSED_ENTITY["release_until"] = $PROCESSED["release_until"];
                                                            $PROCESSED_ENTITY["updated_date"] = time();
                                                            $PROCESSED_ENTITY["updated_by"] = $ENTRADA_USER->getActiveID();
                                                            $PROCESSED_ENTITY["active"] = 1;

                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                            if (!$resource_entity->$method()) {
                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                application_log("error", "Failed to " .$method. " Quiz event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                            }

                                                            if (!$ERROR) {
                                                                last_updated("event", $PROCESSED["event_id"]);

                                                                if ($method == "insert") {
                                                                    history_log($PROCESSED["event_id"], "Attached ".$PROCESSED["quiz_title"]." event quiz.", $ENTRADA_USER->getID());
                                                                } else {
                                                                    history_log($PROCESSED["event_id"], "Updated " . $PROCESSED["quiz_title"] . " event quiz.", $ENTRADA_USER->getID());
                                                                }

                                                                $PROCESSED["next_step"] = 6;
                                                                application_log("success", "Successfully added Event Resource ". $resource_quiz->getAquizID()  ." to event " . $PROCESSED["event_id"]);

                                                                //now we update the other recurring events
                                                                if (isset($recurring_events) && $recurring_events == 1) {
                                                                    if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                        foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                            if (isset($current_resource) && is_object($current_resource)) {
                                                                                $quiz_title = $current_resource->getQuizTitle();
                                                                                $resource_recurring = Models_Quiz_Attached::fetchRowByEventIdTitleUpdate($r_event_id, $quiz_title, $current_resource_date);

                                                                                if (isset($resource_recurring) && is_object($resource_recurring)) {
                                                                                    $resource_recurring_id = $resource_recurring->getID();

                                                                                    $resource_recurring_updated = new Models_Quiz_Attached(array(
                                                                                        "aquiz_id"           => $resource_recurring_id,
                                                                                        "content_type"       => "event",
                                                                                        "content_id"         => $r_event_id,
                                                                                        "required"           => $PROCESSED["required"],
                                                                                        "require_attendance" => $PROCESSED["require_attendance"],
                                                                                        "random_order"      => $PROCESSED["random_order"],
                                                                                        "timeframe"         => $PROCESSED["timeframe"],
                                                                                        "quiz_id"           => $PROCESSED["quiz_id"],
                                                                                        "quiz_title"        => $PROCESSED["quiz_title"],
                                                                                        "quiz_notes"        => $PROCESSED["quiz_notes"],
                                                                                        "quiztype_id"       => $PROCESSED["quiztype_id"],
                                                                                        "quiz_timeout"      => $PROCESSED["quiz_timeout"],
                                                                                        "quiz_attempts"     => $PROCESSED["quiz_attempts"],
                                                                                        "accesses"          => $PROCESSED["accesses"],
                                                                                        "release_date"      => $PROCESSED["release_date"],
                                                                                        "release_until"     => $PROCESSED["release_until"],
                                                                                        "updated_date"      => $PROCESSED["updated_date"],
                                                                                        "updated_by"        => $PROCESSED["updated_by"]
                                                                                    ));

                                                                                    if ($resource_recurring_updated->update()) {
                                                                                        //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                                        $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_updated->getID());

                                                                                        if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                                            $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                                            $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                                            $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                            $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_updated->getID();
                                                                                            $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                            $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                            $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                            $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                            $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                            if (!$resource_entity->update()) {
                                                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                                application_log("error", "Failed to " . $method . " Quiz event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                            } else {
                                                                                                //log success
                                                                                                history_log($r_event_id, "updated " . $PROCESSED["quiz_title"] . " Quiz.", $ENTRADA_USER->getID());
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            } else {
                                                                                //insert recurring
                                                                                $recurring_event = $PROCESSED;
                                                                                $recurring_event["event_id"] = $r_event_id;
                                                                                $recurring_event["content_id"] = $r_event_id;
                                                                                $resource = new Models_Quiz_Attached($recurring_event);

                                                                                if ($resource->insert()) {
                                                                                    last_updated("event", $recurring_event["event_id"]);
                                                                                    //insert

                                                                                    $resource_recurring_id = $resource->getID();

                                                                                    $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_id;
                                                                                    $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                    $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                    if (!$resource_entity->insert()) {
                                                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                        application_log("error", "Failed to " . $method . " Quiz event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                    } else {
                                                                                        //log success
                                                                                        history_log($r_event_id, "inserted " . $PROCESSED["quiz_title"] . " Quiz.", $ENTRADA_USER->getID());
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }

                                                                if (!$ERROR) {
                                                                    echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                                } else {
                                                                    application_log("error", "Failed to " .$method. " Quiz event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                                }

                                                            } else {
                                                                application_log("error", "Failed to " .$method. " Quiz event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                            }
                                                        } else {
                                                            add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                            application_log("error", "Failed to " .$method. " Quiz event resource for event: " . $PROCESSED["event_id"]);
                                                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                        }
                                                    } else {
                                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                    }

                                                    break;
                                            }
                                            break;
                                        case 10 :
                                            if (isset(${$request_var}["event_resource_lti_title_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_lti_title_value"], array("trim", "striptags"))) {
                                                $PROCESSED["lti_title"] = $tmp_input;
                                            } else {
                                                add_error("Please provide a title for this LTI Provider.");
                                            }

                                            if (isset(${$request_var}["event_resource_lti_description_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_lti_description_value"], array("trim", "striptags"))) {
                                                $PROCESSED["lti_notes"] = $tmp_input;
                                            } else {
                                                add_error("You must provide a description for this LTI Provider.");
                                            }

                                            if (isset(${$request_var}["event_resource_lti_url_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_lti_url_value"], array("trim", "striptags"))) {
                                                $PROCESSED["launch_url"] = $tmp_input;
                                            } else {
                                                add_error("Please provide an external LTI launch URL.");
                                            }

                                            if (isset(${$request_var}["event_resource_lti_key_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_lti_key_value"], array("trim", "striptags"))) {
                                                $PROCESSED["lti_key"] = $tmp_input;
                                            } else {
                                                add_error("Please provide an LTI Key / Username.");
                                            }

                                            if (isset(${$request_var}["event_resource_lti_secret_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_lti_secret_value"], array("trim", "striptags"))) {
                                                $PROCESSED["lti_secret"] = $tmp_input;
                                            } else {
                                                add_error("Please provide an LTI Secret / Password.");
                                            }

                                            if (isset(${$request_var}["event_resource_lti_parameters_value"]) && $tmp_input = clean_input(${$request_var}["event_resource_lti_parameters_value"], array("trim", "striptags"))) {
                                                $PROCESSED["lti_params"] = $tmp_input;
                                            }

                                            $PROCESSED["is_required"] = 0;
                                            $PROCESSED["valid_from"] = $PROCESSED["release_date"];
                                            $PROCESSED["valid_until"] = $PROCESSED["release_until"];

                                            if (!$ERROR) {

                                                if (isset($PROCESSED["resource_id"])) {
                                                    $PROCESSED["id"] = $PROCESSED["resource_id"];
                                                    $method = "update";
                                                    $current_resource           = Models_Event_Resource_LtiProvider::fetchRowByID($PROCESSED["id"]);
                                                    $current_resource_entity    = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_entity_id"]);
                                                    $current_resource_date      = $current_resource->getUpdatedDate();
                                                    $current_entity_type        = $current_resource_entity->getEntityType();
                                                } else {
                                                    $method = "insert";
                                                }

                                                $resource_lti_provider = new Models_Event_Resource_LtiProvider($PROCESSED);
                                                if ($resource_lti_provider->$method()) {
                                                    if (isset($PROCESSED["event_resource_entity_id"])) {
                                                        $PROCESSED_ENTITY["event_resource_entity_id"] = $PROCESSED["event_resource_entity_id"];
                                                    }

                                                    $PROCESSED_ENTITY["event_id"] = $PROCESSED["event_id"];
                                                    $PROCESSED_ENTITY["entity_type"] = $PROCESSED["event_resource_type_value"];
                                                    $PROCESSED_ENTITY["entity_value"] = $resource_lti_provider->getID();
                                                    $PROCESSED_ENTITY["release_date"] = $PROCESSED["release_date"];
                                                    $PROCESSED_ENTITY["release_until"] = $PROCESSED["release_until"];
                                                    $PROCESSED_ENTITY["updated_date"] = time();
                                                    $PROCESSED_ENTITY["updated_by"] = $ENTRADA_USER->getActiveID();
                                                    $PROCESSED_ENTITY["active"] = 1;

                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_ENTITY);
                                                    if (!$resource_entity->$method()) {
                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                        application_log("error", "Failed to " .$method. " LTI Provider event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    }

                                                    if (!$ERROR) {
                                                        last_updated("event", $PROCESSED["event_id"]);

                                                        $PROCESSED["next_step"] = 6;
                                                        application_log("success", "Successfully added Event Resource ". $resource_lti_provider->getID()  ." to event " . $PROCESSED["event_id"]);

                                                        //now we update the other recurring events
                                                        if (isset($recurring_events) && $recurring_events == 1) {
                                                            if (isset($PROCESSED["recurring_event_ids"]) && is_array($PROCESSED["recurring_event_ids"]) && !empty($PROCESSED["recurring_event_ids"])) {
                                                                foreach ($PROCESSED["recurring_event_ids"] as $r_event_id) {
                                                                    if (isset($current_resource) && is_object($current_resource)) {
                                                                        $lti_title = $current_resource->getLtiTitle();
                                                                        $resource_recurring = Models_Event_Resource_LtiProvider::fetchRowByEventIdTitleUpdate($r_event_id, $quiz_title, $current_resource_date);

                                                                        if (isset($resource_recurring) && is_object($resource_recurring)) {
                                                                            $resource_recurring_id = $resource_recurring->getID();

                                                                            $resource_recurring_updated = new Models_Event_Resource_LtiProvider(array(
                                                                                "id"            => $resource_recurring_id,
                                                                                "event_id"      => $r_event_id,
                                                                                "is_required"   => $PROCESSED["is_required"],
                                                                                "valid_from"    => $PROCESSED["valid_from"],
                                                                                "valid_until"   => $PROCESSED["valid_until"],
                                                                                "timeframe"     => $PROCESSED["timeframe"],
                                                                                "launch_url"    => $PROCESSED["is_required"],
                                                                                "lti_key"       => $PROCESSED["lti_key"],
                                                                                "lti_secret"    => $PROCESSED["lti_secret"],
                                                                                "lti_params"    => $PROCESSED["lti_params"],
                                                                                "lti_title"     => $PROCESSED["lti_title"],
                                                                                "lti_notes"     => $PROCESSED["lti_notes"],
                                                                                "updated_date"  => $PROCESSED["updated_date"],
                                                                                "updated_by"    => $PROCESSED["updated_by"]
                                                                            ));

                                                                            if ($resource_recurring_updated->update()) {
                                                                                //we get the old entity by using the recurring event ids, and the original entity type and value
                                                                                $old_entity_re = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($r_event_id, $current_entity_type, $resource_recurring_updated->getID());

                                                                                if (isset($old_entity_re) && is_object($old_entity_re)) {
                                                                                    $PROCESSED_RECURRING_ENTITY["event_resource_entity_id"] = $old_entity_re->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["event_id"]         = $r_event_id;
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                                    $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_updated->getID();
                                                                                    $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                                    $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                                    $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                                    $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                                    if (!$resource_entity->update()) {
                                                                                        add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                        application_log("error", "Failed to " . $method . " LTI Provider event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                                    } else {
                                                                                        //log success\
                                                                                        history_log($r_event_id, "updated " . $PROCESSED["lti_title"] . " LTI Provider.", $ENTRADA_USER->getID());
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    } else {
                                                                        //insert recurring
                                                                        $recurring_event = $PROCESSED;
                                                                        $recurring_event["event_id"] = $r_event_id;
                                                                        $resource = new Models_Event_Resource_LtiProvider($recurring_event);

                                                                        if ($resource->insert()) {
                                                                            last_updated("event", $recurring_event["event_id"]);
                                                                            //insert

                                                                            $resource_recurring_id = $resource->getID();

                                                                            $PROCESSED_RECURRING_ENTITY["event_id"]         = $recurring_event["event_id"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_type"]      = $PROCESSED["event_resource_type_value"];
                                                                            $PROCESSED_RECURRING_ENTITY["entity_value"]     = $resource_recurring_id;
                                                                            $PROCESSED_RECURRING_ENTITY["release_date"]     = $PROCESSED["release_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["release_until"]    = $PROCESSED["release_until"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_date"]     = $PROCESSED_ENTITY["updated_date"];
                                                                            $PROCESSED_RECURRING_ENTITY["updated_by"]       = $ENTRADA_USER->getActiveID();
                                                                            $PROCESSED_RECURRING_ENTITY["active"]           = 1;

                                                                            $resource_entity = new Models_Event_Resource_Entity($PROCESSED_RECURRING_ENTITY);
                                                                            if (!$resource_entity->insert()) {
                                                                                add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                                                application_log("error", "Failed to " . $method . " LTI Provider event resource entity for event: " . $r_event_id . " DB said:" . $db->ErrorMsg());
                                                                            } else {
                                                                                //log success
                                                                                history_log($r_event_id, "inserted " . $PROCESSED["lti_title"] . " LTI Provider.", $ENTRADA_USER->getID());
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        if (!$ERROR) {
                                                            echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"], "sub_step" => 1, "event_resource_type" => $PROCESSED["event_resource_type_value"])));
                                                        } else {
                                                            application_log("error", "Failed to " .$method. " LTI Provider event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                        }
                                                    }
                                                }  else {
                                                    add_error("A problem occured while attempting to save this event resource. Please try again later.");
                                                    application_log("error", "Failed to " .$method. " LTI Provider event resource entity for event: " . $PROCESSED["event_id"] ." DB said:" . $db->ErrorMsg());
                                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                                }
                                            } else {
                                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                            }
                                            break;
                                    }
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                                break;
                        }
                        break;
                    case "delete" :
                        if (isset(${$request_var}["entity_id"]) && $tmp_input = clean_input(${$request_var}["entity_id"], array("trim", "int"))) {
                            $PROCESSED["entity_id"] = $tmp_input;
                            $PROCESSED["entities"] = array($PROCESSED["entity_id"]);
                        } else {
                            add_error("Unable to delete the selected resource, please try again later.");
                        }

                        if (isset(${$request_var}["entities"])) {
                            if (is_string(${$request_var}["entities"])) {
                                $PROCESSED["recurring_entities"] = explode(",", ${$request_var}["entities"]);
                                $PROCESSED["entities"] = array_merge($PROCESSED["entities"], $PROCESSED["recurring_entities"]);
                            }
                        }

                        $deleted = 0;

                        if (!$ERROR) {
                            if (isset($PROCESSED["entities"]) && is_array($PROCESSED["entities"]) && !empty($PROCESSED["entities"])) {
                                foreach ($PROCESSED["entities"] as $entity_id) {
                                    $resource_entity = Models_Event_Resource_Entity::fetchRowByID($entity_id);
                                    if ($resource_entity) {
                                        $resource_entity_array = $resource_entity->toArray();
                                        $entity = $resource_entity->getResource();
                                        if (!$resource_entity->delete() && $entity) {
                                            if(!$entity->delete()) {
                                                add_error("Unable to delete the selected resource, please try again later.");
                                            }

                                            if (!$ERROR) {
                                                switch ($resource_entity->getEntityType()) {
                                                    case 1 :
                                                        $resource_type = "podcast";
                                                        $resource_title = ($entity->getFileTitle() != "" ? $entity->getFileTitle() : $entity->getFileName());
                                                        break;
                                                    case 5 :
                                                        $resource_type = "lecture notes";
                                                        $resource_title = ($entity->getFileTitle() != "" ? $entity->getFileTitle() : $entity->getFileName());
                                                        break;
                                                    case 6 :
                                                        $resource_type = "lecture slides";
                                                        $resource_title = ($entity->getFileTitle() != "" ? $entity->getFileTitle() : $entity->getFileName());
                                                        break;
                                                    case 11 :
                                                        $resource_type = "other files";
                                                        $resource_title = ($entity->getFileTitle() != "" ? $entity->getFileTitle() : $entity->getFileName());
                                                        break;
                                                    case 2 :
                                                        $resource_type = "classwork";
                                                        $resource_title = "";
                                                        break;
                                                    case 3 :
                                                        $resource_type = "link";
                                                        $resource_title = ($entity->getLinkTitle() != "" ? $entity->getLinkTitle() : $entity->getLink());
                                                        break;
                                                    case 7 :
                                                        $resource_type = "learning module";
                                                        $resource_title = ($entity->getLinkTitle() != "" ? $entity->getLinkTitle() : $entity->getLink());
                                                        break;
                                                    case 4 :
                                                        $resource_type = "homework";
                                                        $resource_title = "";
                                                        break;
                                                    case 8 :
                                                        $resource_type = "quiz";
                                                        $resource_title = $entity->getQuizTitle();
                                                        break;
                                                    case 9 :
                                                        $resource_type = "textbook reading";
                                                        $resource_title = "";
                                                        break;
                                                    case 10 :
                                                        $resource_type = "LTI Provider";
                                                        $resource_title = "";
                                                        break;
                                                    case 12 :
                                                        $resource_type = "Exam";
                                                        $resource_title = "";
                                                        break;
                                                }

                                                history_log($resource_entity->getEventID(), "deleted ". $resource_title . " " . $resource_type, $ENTRADA_USER->getID());
                                                $deleted++;
                                            } else {
                                                echo json_encode(array("status" => "error", "data" => array("A problem occured while attempting to delete this resource, please try again later.")));
                                            }
                                        } else {
                                            echo json_encode(array("status" => "error", "data" => array("A problem occured while attempting to delete this resource, please try again later.")));
                                        }
                                    }


                                    switch ($resource_entity_array["entity_type"]) {
                                        case 1 :
                                        case 5 :
                                        case 6 :
                                        case 11 :
                                            if (@file_exists(FILE_STORAGE_PATH . "/" . $resource_entity_array["entity_value"])) {
                                                @unlink(FILE_STORAGE_PATH . "/" . $resource_entity_array["entity_value"]);
                                            }
                                            break;
                                    }
                                }
                                if (!$ERROR) {
                                    echo json_encode(array("status" => "success", "data" => array("Successfully removed event resource" . ($deleted > 1 ? "s" : ""))));
                                }
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                }
                break;
            case "GET" :
                switch ($method) {
                    case "event_resource" :
                        if (isset(${$request_var}["event_resource_id"]) && $tmp_input = clean_input(${$request_var}["event_resource_id"], array("trim", "int"))) {
                            $PROCESSED["event_resource_id"] = $tmp_input;
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("A problem occured while attempting to retrieve this event resource.")));
                        }

                        if (isset($PROCESSED["event_resource_id"])) {
                            $entity = Models_Event_Resource_Entity::fetchRowByID($PROCESSED["event_resource_id"]);
                            if ($entity) {
                                switch ($entity->getEntityType()) {
                                    case 1 :
                                    case 5 :
                                    case 6 :
                                    case 11 :
                                        $resource_type_title = "";
                                        if ($entity->getEntityType() == 1) {
                                            $resource_type_title = "Audio / Video";
                                        } else if ($entity->getEntityType() == 5) {
                                            $resource_type_title = "Lecture Notes";
                                        } else if ($entity->getEntityType() == 6) {
                                            $resource_type_title = "Lecture Slides";
                                        } else if ($entity->getEntityType() == 11) {
                                            $resource_type_title = "Other";
                                        }

                                        $resource = Models_Event_Resource_File::fetchRowByID($entity->getEntityValue());
                                        if ($resource) {
                                            $resource_array = array(
                                                "entity_id" => $entity->getID(),
                                                "resource_id" => $resource->getID(),
                                                "title" => ($resource->getFileTitle() != "" ? $resource->getFileTitle() : $resource->getFileName()),
                                                "file_name" => $resource->getFileName(),
                                                "file_size" => readable_size($resource->getFileSize()),
                                                "description" => $resource->getFileNotes(),
                                                "accesses" => $resource->getAccesses(),
                                                "access_method" => $resource->getAccessMethod(),
                                                "timeframe" => $resource->getTimeframe(),
                                                "required" => $resource->getRequired(),
                                                "release_date" => ($resource->getReleaseDate() != "0" ? date("Y-m-d", $resource->getReleaseDate()) : ""),
                                                "release_until" => ($resource->getReleaseUntil() != "0" ? date("Y-m-d", $resource->getReleaseUntil()) : ""),
                                                "start_time" => ($resource->getReleaseDate() != "0" ? date("H:i", $resource->getReleaseDate()) : ""),
                                                "finish_time" => ($resource->getReleaseUntil() != "0" ? date("H:i", $resource->getReleaseUntil()) : ""),
                                                "resource_type" => $entity->getEntityType(),
                                                "resource_type_title" => $resource_type_title
                                            );
                                        }
                                        break;
                                    case 8 :
                                        $resource = Models_Quiz_Attached::fetchRowByID($entity->getEntityValue());
                                        if ($resource) {
                                            $resource_array = array(
                                                "entity_id" => $entity->getID(),
                                                "resource_id" => $resource->getAQuizID(),
                                                "title" => $resource->getQuizTitle(),
                                                "url" => ENTRADA_RELATIVE . "/file-event.php?id=" . (int) $resource->getAquizID(),
                                                "quiz_id" => $resource->getQuizID(),
                                                "description" => $resource->getQuizNotes(),
                                                "require_attendance" => $resource->getRequireAttendance(),
                                                "random_order" => $resource->getRandomOrder(),
                                                "quiz_timeout" => $resource->getQuizTimeout(),
                                                "quiz_attempts" => $resource->getQuizAttempts(),
                                                "quiztype_id" => $resource->getQuiztypeID(),
                                                "accesses" => $resource->getAccesses(),
                                                "timeframe" => $resource->getTimeframe(),
                                                "required" => $resource->getRequired(),
                                                "release_date" => ($resource->getReleaseDate() != "0" ? date("Y-m-d", $resource->getReleaseDate()) : ""),
                                                "release_until" => ($resource->getReleaseUntil() != "0" ? date("Y-m-d", $resource->getReleaseUntil()) : ""),
                                                "start_time" => ($resource->getReleaseDate() != "0" ? date("H:i", $resource->getReleaseDate()) : ""),
                                                "finish_time" => ($resource->getReleaseUntil() != "0" ? date("H:i", $resource->getReleaseUntil()) : ""),
                                                "resource_type" => $entity->getEntityType(),
                                                "resource_type_title" => "Quiz"
                                            );
                                        }
                                        break;
                                    case 4 :
                                        $resource = Models_Event_Resource_Homework::fetchRowByID($entity->getEntityValue());
                                        if ($resource) {
                                            $resource_array = array(
                                                "entity_id" => $entity->getID(),
                                                "resource_id" => $resource->getID(),
                                                "description" => $resource->getResourceHomework(),
                                                "timeframe" => $resource->getTimeframe(),
                                                "required" => $resource->getRequired(),
                                                "release_date" => ($resource->getReleaseDate() != "0" ? date("Y-m-d", $resource->getReleaseDate()) : ""),
                                                "release_until" => ($resource->getReleaseUntil() != "0" ? date("Y-m-d", $resource->getReleaseUntil()) : ""),
                                                "start_time" => ($resource->getReleaseDate() != "0" ? date("H:i", $resource->getReleaseDate()) : ""),
                                                "finish_time" => ($resource->getReleaseUntil() != "0" ? date("H:i", $resource->getReleaseUntil()) : ""),
                                                "resource_type" => $entity->getEntityType(),
                                                "resource_type_title" => "Homework"
                                            );
                                        }
                                        break;
                                    case 2 :
                                        $resource = Models_Event_Resource_Classwork::fetchRowByID($entity->getEntityValue());
                                        if ($resource) {
                                            $resource_array = array(
                                                "entity_id" => $entity->getID(),
                                                "resource_id" => $resource->getID(),
                                                "description" => $resource->getResourceClasswork(),
                                                "timeframe" => $resource->getTimeframe(),
                                                "required" => $resource->getRequired(),
                                                "release_date" => ($resource->getReleaseDate() != "0" ? date("Y-m-d", $resource->getReleaseDate()) : ""),
                                                "release_until" => ($resource->getReleaseUntil() != "0" ? date("Y-m-d", $resource->getReleaseUntil()) : ""),
                                                "start_time" => ($resource->getReleaseDate() != "0" ? date("H:i", $resource->getReleaseDate()) : ""),
                                                "finish_time" => ($resource->getReleaseUntil() != "0" ? date("H:i", $resource->getReleaseUntil()) : ""),
                                                "resource_type" => $entity->getEntityType(),
                                                "resource_type_title" => "Class Work"
                                            );
                                        }
                                        break;
                                    case 9 :
                                        $resource = Models_Event_Resource_TextbookReading::fetchRowByID($entity->getEntityValue());
                                        if ($resource) {
                                            $resource_array = array(
                                                "entity_id" => $entity->getID(),
                                                "resource_id" => $resource->getID(),
                                                "description" => $resource->getResourceTextbookReading(),
                                                "timeframe" => $resource->getTimeframe(),
                                                "required" => $resource->getRequired(),
                                                "release_date" => ($resource->getReleaseDate() != "0" ? date("Y-m-d", $resource->getReleaseDate()) : ""),
                                                "release_until" => ($resource->getReleaseUntil() != "0" ? date("Y-m-d", $resource->getReleaseUntil()) : ""),
                                                "start_time" => ($resource->getReleaseDate() != "0" ? date("H:i", $resource->getReleaseDate()) : ""),
                                                "finish_time" => ($resource->getReleaseUntil() != "0" ? date("H:i", $resource->getReleaseUntil()) : ""),
                                                "resource_type" => $entity->getEntityType(),
                                                "resource_type_title" => "Textbook Reading"
                                            );
                                        }
                                        break;
                                    case 3 :
                                    case 7 :
                                        $resource = Models_Event_Resource_Link::fetchRowByID($entity->getEntityValue());
                                        if ($resource) {
                                            $resource_array = array(
                                                "entity_id" => $entity->getID(),
                                                "resource_id" => $resource->getID(),
                                                "link" => $resource->getLink(),
                                                "title" => ($resource->getLinkTitle() != "" ? $resource->getLinkTitle() : $resource->getLink()),
                                                "url" => ENTRADA_RELATIVE . "/link-event.php?id=" . (int) $resource->getID(),
                                                "description" => $resource->getLinkNotes(),
                                                "timeframe" => $resource->getTimeframe(),
                                                "proxyify" => $resource->getProxify(),
                                                "required" => $resource->getRequired(),
                                                "accesses" => $resource->getAccesses(),
                                                "release_date" => ($resource->getReleaseDate() != "0" ? date("Y-m-d", $resource->getReleaseDate()) : ""),
                                                "release_until" => ($resource->getReleaseUntil() != "0" ? date("Y-m-d", $resource->getReleaseUntil()) : ""),
                                                "start_time" => ($resource->getReleaseDate() != "0" ? date("H:i", $resource->getReleaseDate()) : ""),
                                                "finish_time" => ($resource->getReleaseUntil() != "0" ? date("H:i", $resource->getReleaseUntil()) : ""),
                                                "resource_type" => $entity->getEntityType(),
                                                "resource_type_title" => "External Link"
                                            );
                                        }
                                        break;
                                    case 10 :
                                        $resource = Models_Event_Resource_LtiProvider::fetchRowByID($entity->getEntityValue());
                                        if ($resource) {
                                            $resource_array = array(
                                                "entity_id" => $entity->getID(),
                                                "resource_id" => $resource->getID(),
                                                "title" => $resource->getLtiTitle(),
                                                "link" => $resource->getLaunchUrl(),
                                                "description" => $resource->getLtiNotes(),
                                                "lti_key" => $resource->getLtiKey(),
                                                "lti_secret" => $resource->getLtiSecret(),
                                                "lti_params" => $resource->getLtiParams(),
                                                "timeframe" => $resource->getTimeframe(),
                                                "required" => $resource->getRequired(),
                                                "release_date" => ($resource->getValidFrom() != "0" ? date("Y-m-d", $resource->getValidFrom()) : ""),
                                                "release_until" => ($resource->getValidUntil() != "0" ? date("Y-m-d", $resource->getValidUntil()) : ""),
                                                "start_time" => ($resource->getReleaseDate() != "0" ? date("H:i", $resource->getReleaseDate()) : ""),
                                                "finish_time" => ($resource->getReleaseUntil() != "0" ? date("H:i", $resource->getReleaseUntil()) : ""),
                                                "resource_type" => $entity->getEntityType(),
                                                "resource_type_title" => "LTI Provider"
                                            );
                                        }
                                        break;
                                }
                                echo json_encode(array("status" => "success", "data" => $resource_array));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array("A problem occured while attempting to retrieve this event resource.gfdgdfds")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("A problem occured while attempting to retrieve this event resource.")));
                        }
                        break;
                    case "event_resource_types" :
                        $event_resource_types = Models_Event_Resource_Type::fetchAllRecords();
                        if ($event_resource_types) {
                            $event_resources = array();
                            foreach ($event_resource_types as $event_resource_type) {
                                $event_resources[] = $event_resource_type->toArray();
                            }
                            echo json_encode(array("status" => "success", "data" => $event_resources));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("There are currently no Event Resource Types in the system.")));
                        }
                        break;
                    case "recurring_events" :
                        if (isset(${$request_var}["event_id"]) && $tmp_input = clean_input(${$request_var}["event_id"], array("trim", "int"))) {
                            $PROCESSED["event_id"] = $tmp_input;
                        } else {
                            add_error("Invalid event ID supplied.");
                        }

                        $data = array("recurring_events" => false);

                        //get any recurring events as well
                        $recurring_events = Models_Event::fetchAllRecurringByEventID($PROCESSED["event_id"]);
                        if (isset($recurring_events) && is_array($recurring_events) && !empty($recurring_events)) {
                            $PROCESSED["recurring_events"] = $recurring_events;
                        }

                        if (isset($PROCESSED["recurring_events"])) {
                            $R_Events = array();
                            foreach ($PROCESSED["recurring_events"] as $events) {
                                if (isset($events) && is_object($events)) {
                                    $R_Events[] = $events->getID();
                                }
                            }
                            $data["recurring_events"] = true;
                            $data["recurring_event_ids"] = $R_Events;
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "success", "data" => $data));
                        }
                        break;
                    case "recurring_events_view":
                        if (isset(${$request_var}["recurring_event_ids"])) {
                            $recurring_event_ids = ${$request_var}["recurring_event_ids"];
                            $ids_checked = ${$request_var}["ids_checked"];

                            $data["recurring_event_ids"] = $recurring_event_ids;
                            $data["html"] = "";
                            $data["sub_step"] = 1;
                            if (isset($recurring_event_ids) && is_array($recurring_event_ids)) {
                                $data["recurring_events"] = true;
                                foreach ($recurring_event_ids as $recurring_event_id) {
                                    $event = Models_Event::get($recurring_event_id);
                                    $row = "";
                                    if (isset($event) && is_object($event)) {
                                        if (isset($ids_checked) && is_array($ids_checked) && !empty($ids_checked)) {
                                            if (in_array($event->getID(), $ids_checked)) {
                                                $checked = " checked=\"checked\"";
                                            } else {
                                                $checked = "";
                                            }
                                        } else {
                                            $checked = "";
                                        }

                                        $row .= "<div style=\"padding-left: 65px\">";
                                        $row .= "   <label class=\"checkbox\" for=\"r_event_id_" . $event->getID() . "\">";
                                        $row .= "       <input type=\"checkbox\" id=\"r_event_id_" . $event->getID() . "\" class=\"r_events\" name=\"r_events[]\" value=\"" . $event->getID() . "\" data-id=\"" .  $event->getID() . "\"" . $checked . ">";
                                        $row .=         html_encode($event->getEventTitle());
                                        $row .= "       on " . html_encode(date(DEFAULT_DATE_FORMAT, $event->getEventStart())) . ".";
                                        $row .= "    </label>";
                                        $row .= "</div>";

                                        $data["html"] .= $row;
                                    }
                                }
                            }

                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            $data["msg"] = "No Recurring Events";
                            $data["recurring_events"] = false;
                            echo json_encode(array("status" => "success", "data" => $data));
                        }
                        break;
                    case "recurring_events_resource_view":
                        if (isset(${$request_var}["recurring_event_ids"])) {
                            $recurring_event_ids = ${$request_var}["recurring_event_ids"];
                            $entity_id      = ${$request_var}["data_id"];

                            $entity         = Models_Event_Resource_Entity::fetchRowByID($entity_id);
                            $entity_type    = $entity->getEntityType();
                            $entity_value   = $entity->getEntityValue();

                            $data["html"]   = "";
                            $html           = "";

                            //gets the resource title
                            switch ($entity_type) {
                                case 1 :
                                case 5 :
                                case 6 :
                                case 11 :
                                    //files
                                    $resource               = Models_Event_Resource_File::fetchRowByID($entity->getEntityValue());
                                    $resource_name          = $resource->getFileName();
                                    $resource_title         = $resource->getFileTitle();
                                    $resource_update_time   = $resource->getUpdatedDate();
                                    break;
                                case 8 :
                                    //quiz
                                    $resource               = Models_Quiz_Attached::fetchRowByID($entity->getEntityValue());
                                    $resource_title         = $resource->getQuizTitle();
                                    $resource_update_time   = $resource->getUpdatedDate();
                                    break;
                                case 2:
                                    //classwork
                                    $resource               = Models_Event_Resource_Classwork::fetchRowByID($entity->getEntityValue());
                                    $resource_title         = $resource->getResourceClasswork();
                                    $resource_update_time   = $resource->getUpdatedDate();
                                    break;
                                case 4:
                                    //homework
                                    $resource               = Models_Event_Resource_Homework::fetchRowByID($entity->getEntityValue());
                                    $resource_title         = $resource->getResourceHomework();
                                    $resource_update_time   = $resource->getUpdatedDate();
                                    break;
                                case 9:
                                    //textbook
                                    $resource               = Models_Event_Resource_TextbookReading::fetchRowByID($entity->getEntityValue());
                                    $resource_title         = $resource->getResourceTextbookReading();
                                    $resource_update_time   = $resource->getUpdatedDate();
                                    break;
                                case 3:
                                case 7:
                                    //links
                                    $resource               = Models_Event_Resource_Link::fetchRowByID($entity->getEntityValue());
                                    $resource_title         = $resource->getLink();
                                    $resource_update_time   = $resource->getUpdatedDate();
                                    break;
                                case 10:
                                    //lti
                                    $resource               = Models_Event_Resource_LtiProvider::fetchRowByID($entity->getEntityValue());
                                    $resource_title         = $resource->getLtiTitle();
                                    $resource_update_time   = $resource->getUpdatedDate();
                                    break;
                                case 12 :
                                    //exam
                                    $resource = false;
                                    break;
                            }

                            if (isset($recurring_event_ids) && is_string($recurring_event_ids)) {
                                $recurring_event_ids = json_decode($recurring_event_ids);
                            }

                            if ($resource && isset($recurring_event_ids) && is_array($recurring_event_ids)) {
                                $html .= "<table class=\"table table-striped table-bordered\">";
                                $html .= "<thead>";
                                $html .= "<tr>";
                                $html .= "<td></td>";//checkbox
                                $html .= "<td><strong>Title</strong></td>";
                                $html .= "<td><strong>Event</strong></td>";
                                $html .= "</tr>";
                                $html .= "</thead>";
                                $html .= "<tbody>";
                                foreach ($recurring_event_ids as $recurring_event_id) {
                                    $recurring_event = Models_Event::get($recurring_event_id);
                                    $row = "";
                                    if (isset($recurring_event) && is_object($recurring_event)) {
                                        //get matching resource and entity
                                        switch ($entity_type) {
                                            case 1 :
                                            case 5 :
                                            case 6 :
                                            case 11 :
                                                //files
                                                $resource = Models_Event_Resource_File::fetchRowByEventIDNameUpdate($recurring_event->getID(), $resource_name, $resource_update_time);

                                                if (isset($resource) && is_object($resource)) {
                                                    $resource_value = $resource->getID();
                                                }

                                                if (!$resource_title) {
                                                    $resource_title = $resource_name;
                                                }
                                            break;
                                            case 8 :
                                                //quiz
                                                $resource = Models_Quiz_Attached::fetchRowByEventIdTitleUpdate($recurring_event->getID(), $resource_title, $resource_update_time);

                                                if (isset($resource) && is_object($resource)) {
                                                    $resource_value = $resource->getID();
                                                }
                                            break;
                                            case 2:
                                                //classwork
                                                $resource = Models_Event_Resource_Classwork::fetchRowByEventIdResourceUpdate($recurring_event->getID(), $resource_title, $resource_update_time);

                                                if (isset($resource) && is_object($resource)) {
                                                    $resource_value = $resource->getID();
                                                }
                                            break;
                                            case 4:
                                                //homework
                                                $resource = Models_Event_Resource_Homework::fetchRowByEventIdResourceUpdate($recurring_event->getID(), $resource_title, $resource_update_time);

                                                if (isset($resource) && is_object($resource)) {
                                                    $resource_value = $resource->getID();
                                                }
                                            break;
                                            case 9:
                                                //textbook
                                                $resource = Models_Event_Resource_TextbookReading::fetchRowByEventIdResourceUpdate($recurring_event->getID(), $resource_title, $resource_update_time);

                                                if (isset($resource) && is_object($resource)) {
                                                    $resource_value = $resource->getID();
                                                }
                                            break;
                                            case 3:
                                            case 7:
                                                //links
                                                $resource = Models_Event_Resource_Link::fetchRowByEventIDLinkUpdate($recurring_event->getID(), $resource_title, $resource_update_time);

                                                if (isset($resource) && is_object($resource)) {
                                                    $resource_value = $resource->getID();
                                                }
                                            break;
                                            case 10:
                                                //lti
                                                $resource = Models_Event_Resource_LtiProvider::fetchRowByEventIdTitleUpdate($recurring_event->getID(), $resource_title, $resource_update_time);

                                                if (isset($resource) && is_object($resource)) {
                                                    $resource_value = $resource->getID();
                                                }
                                            break;

                                            case 12 :
                                                //exam
                                                break;
                                            default :
                                                continue;
                                            break;
                                        }

                                        if (isset($resource_value)) {
                                            $recurring_entity = Models_Event_Resource_Entity::fetchRowByEventIdEntityTypeEntityValue($recurring_event->getID(), $entity_type, $resource_value, 1);
                                        }

                                        if (isset($recurring_entity) && is_object($recurring_entity)) {
                                            $row .= "<tr>\n";
                                            $row .= "<td\n>";
                                            $row .= "<input type=\"checkbox\" id=\"entity_" . $recurring_entity->getID() . "\" class=\"entity\" name=\"entity[]\" value=\"" . $recurring_entity->getID() . "\" data-event-id=" .  $recurring_event->getID() . " data-entity-id=" .  $recurring_entity->getID() . " checked=checked>\n";
                                            $row .= "</td>\n";
                                            $row .= "<td>\n";
                                            $row .= html_encode(substr($resource_title, 0, 30));
                                            $row .= "</td>\n";
                                            $row .= "<td>\n";
                                            $row .= "<span class=\"content-small\">" . html_encode($recurring_event->getEventTitle()) . " on ";
                                            $row .= html_encode(date(DEFAULT_DATE_FORMAT, $recurring_event->getEventStart())) . "</span>\n";
                                            $row .= "</td>\n";
                                            $row .= "</tr>\n";
                                            $html .= $row;
                                        }
                                    }
                                }

                                $html .= "</tbody>";
                                $html .= "</table>";

                                $data["html"] = $html;
                            } else {
                                $data["resource"] = false;
                            }


                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            $data["msg"] = "No Recurring Events";
                            $data["recurring_events"] = false;
                            echo json_encode(array("status" => "success", "data" => $data));
                        }
                        break;
                    case "quizzes" :
                        $quizzes = false;

                        if (isset(${$request_var}["quiz_id"]) && $tmp_input = clean_input(${$request_var}["quiz_id"], array("trim", "int"))) {
                            $PROCESSED["quiz_id"] = $tmp_input;
                        }

                        if (isset($PROCESSED["quiz_id"])) {
                            $quizzes = Models_Quiz::fetchAllRecordsByProxyIDQuizID($ENTRADA_USER->getActiveId(), $PROCESSED["quiz_id"]);
                        } else {
                            $quizzes = Models_Quiz::fetchAllRecordsByProxyID($ENTRADA_USER->getActiveId());
                        }

                        if ($quizzes) {
                            $quiz_array = array();
                            foreach ($quizzes as $quiz) {
                                $quiz_array[] = array("quiz_id" => $quiz->getQuizID(), "quiz_title" => $quiz->getQuizTitle(), "quiz_description" => $quiz->getQuizDescription());
                            }
                            echo json_encode(array("status" => "success", "data" => $quiz_array));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("No quizzes found.")));
                        }
                        break;
                    case "event_resources" :
                        if (isset(${$request_var}["event_id"]) && $tmp_input = clean_input(${$request_var}["event_id"], array("trim", "int"))) {
                            $PROCESSED["event_id"] = $tmp_input;
                        } else {
                            add_error("Invalid event ID supplied.");
                        }

                        if (!$ERROR) {
                            $event_resource_entities = Models_Event_Resource_Entity::fetchAllByEventID($PROCESSED["event_id"]);
                            if ($event_resource_entities) {
                                $entities = array(
                                    "pre" => array(),
                                    "during" => array(),
                                    "post" => array(),
                                    "none" => array()
                                );

                                foreach ($event_resource_entities as $entity) {
                                    switch ($entity->getEntityType()) {
                                        case 1 :
                                        case 5 :
                                        case 6 :
                                        case 11 :
                                            $resource = Models_Event_Resource_File::fetchRowByID($entity->getEntityValue());
                                            if ($resource && is_object($resource)) {
                                                $resource_type_title = "";
                                                if ($entity->getEntityType() == 1) {
                                                    $resource_type_title = "Podcast";
                                                } else if ($entity->getEntityType() == 5) {
                                                    $resource_type_title = "Lecture Notes";
                                                } else if ($entity->getEntityType() == 6) {
                                                    $resource_type_title = "Lecture Slides";
                                                } else if ($entity->getEntityType() == 11) {
                                                    $resource_type_title = "Other";
                                                } else if ($entity->getEntityType() == 12) {
                                                    $resource_type_title = "Podcast";
                                                }

                                                $resource = Models_Event_Resource_File::fetchRowByID($entity->getEntityValue());

                                                $resource_array = array(
                                                    "entity_id" => $entity->getID(),
                                                    "resource_id" => $resource->getID(),
                                                    "title" => ($resource->getFileTitle() != "" ? $resource->getFileTitle() : $resource->getFileName()),
                                                    "url" => ENTRADA_RELATIVE . "/file-event.php?id=" . (int) $resource->getID(),
                                                    "file_name" => $resource->getFileName(),
                                                    "file_size" => readable_size($resource->getFileSize()),
                                                    "description" => $resource->getFileNotes(),
                                                    "access_method" => $resource->getAccessMethod(),
                                                    "timeframe" => $resource->getTimeframe(),
                                                    "required" => $resource->getRequired(),
                                                    "resource_type" => $entity->getEntityType(),
                                                    "resource_type_title" => $resource_type_title
                                                );

                                                $resource_array["release_date"] = "";
                                                $resource_array["release_until"] = "";
                                                if (((int) $resource->getReleaseDate()) && ($resource->getReleaseDate() > time())) {
                                                    $resource_array["release_date"] =  "This file will be available for downloading <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseDate())."</strong>.";
                                                } elseif (((int) $resource->getReleaseUntil()) && ($resource->getReleaseUntil() < time())) {
                                                    $resource_array["release_until"] = "This file was only available for download until <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseUntil())."</strong>.";
                                                }
                                            }
                                            break;
                                        case 8 :
                                            $resource = Models_Quiz_Attached::fetchRowByID($entity->getEntityValue());
                                            if ($resource && is_object($resource)) {

                                                $resource_array = array(
                                                    "entity_id" => $entity->getID(),
                                                    "resource_id" => $resource->getAquizID(),
                                                    "title" => $resource->getQuizTitle(),
                                                    "quiz_id" => $resource->getQuizID(),
                                                    "description" => $resource->getQuizNotes(),
                                                    "require_attendance" => $resource->getRequireAttendance(),
                                                    "random_order" => $resource->getRandomOrder(),
                                                    "quiz_timeout" => $resource->getQuizTimeout(),
                                                    "quiz_attempts" => $resource->getQuizAttempts(),
                                                    "quiztype_id" => $resource->getQuiztypeID(),
                                                    "timeframe" => $resource->getTimeframe(),
                                                    "required" => $resource->getRequired(),
                                                    "resource_type" => $entity->getEntityType(),
                                                    "resource_type_title" => "Quiz"
                                                );

                                                $resource_array["release_date"] = "";
                                                $resource_array["release_until"] = "";

                                                if ((int) $resource->getReleaseDate() && (int) $resource->getReleaseUntil()) {
                                                    $resource_array["release_until"] .= "This quiz ".($resource->getReleaseUntil() > time() ? "is" : "was only")." available from <strong>".date(DEFAULT_DATE_FORMAT, html_encode($resource->getReleaseDate()))."</strong> to <strong>".date(DEFAULT_DATE_FORMAT, html_encode($resource->getReleaseUntil()))."</strong>.";
                                                } elseif ((int) $resource->getReleaseDate()) {
                                                    $resource_array["release_until"] .= "This quiz ".($resource->getReleaseDate() > time() ? "will become" : "became")." available on <strong>".date(DEFAULT_DATE_FORMAT, html_encode($resource->getReleaseDate()))."</strong>.";
                                                } elseif ((int) $resource->getReleaseUntil()) {
                                                    $resource_array["release_until"] .= "This quiz ".($resource->getReleaseUntil() > time() ? "is" : "was only")." available until <strong>".date(DEFAULT_DATE_FORMAT, html_encode($resource->getReleaseUntil()))."</strong>.";
                                                } else {
                                                    $resource_array["release_until"] = "This quiz is available indefinitely.";
                                                }
                                            }
                                            break;
                                        case 4 :
                                            $resource = Models_Event_Resource_Homework::fetchRowByID($entity->getEntityValue());
                                            if ($resource && is_object($resource)) {
                                                $resource_array = array(
                                                    "entity_id" => $entity->getID(),
                                                    "resource_id" => $resource->getID(),
                                                    "title" => "Homework",
                                                    "description" => $resource->getResourceHomework(),
                                                    "timeframe" => $resource->getTimeframe(),
                                                    "required" => $resource->getRequired(),
                                                    "resource_type" => $entity->getEntityType(),
                                                    "resource_type_title" => "Homework"
                                                );

                                                $resource_array["release_date"] = "";
                                                $resource_array["release_until"] = "";
                                                if (((int) $resource->getReleaseDate()) && ($resource->getReleaseDate() > time())) {
                                                    $resource_array["release_date"] =  "This homework will become accessible <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseDate())."</strong>.";
                                                } elseif (((int) $resource->getReleaseUntil()) && ($resource->getReleaseUntil() < time())) {
                                                    $resource_array["release_until"] = "This homework was only available until <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseUntil())."</strong>.";
                                                }
                                            }
                                            break;
                                        case 2 :
                                            $resource = Models_Event_Resource_Classwork::fetchRowByID($entity->getEntityValue());
                                            if ($resource && is_object($resource)) {
                                                $resource_array = array(
                                                    "entity_id" => $entity->getID(),
                                                    "resource_id" => $resource->getID(),
                                                    "title" => "Class Work",
                                                    "description" => $resource->getResourceClasswork(),
                                                    "timeframe" => $resource->getTimeframe(),
                                                    "required" => $resource->getRequired(),
                                                    "resource_type" => $entity->getEntityType(),
                                                    "resource_type_title" => "Class Work"
                                                );

                                                $resource_array["release_date"] = "";
                                                $resource_array["release_until"] = "";
                                                if (((int) $resource->getReleaseDate()) && ($resource->getReleaseDate() > time())) {
                                                    $resource_array["release_date"] =  "This classwork will become accessible <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseDate())."</strong>.";
                                                } elseif (((int) $resource->getReleaseUntil()) && ($resource->getReleaseUntil() < time())) {
                                                    $resource_array["release_until"] = "This classwork was only available until <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseUntil())."</strong>.";
                                                }
                                            }
                                            break;
                                        case 9 :
                                            $resource = Models_Event_Resource_TextbookReading::fetchRowByID($entity->getEntityValue());
                                            if ($resource && is_object($resource)) {
                                                $resource_array = array(
                                                    "entity_id" => $entity->getID(),
                                                    "resource_id" => $resource->getID(),
                                                    "title" => "Textbook Reading",
                                                    "description" => $resource->getResourceTextbookReading(),
                                                    "timeframe" => $resource->getTimeframe(),
                                                    "required" => $resource->getRequired(),
                                                    "resource_type" => $entity->getEntityType(),
                                                    "resource_type_title" => "Textbook Reading"
                                                );

                                                $resource_array["release_date"] = "";
                                                $resource_array["release_until"] = "";
                                                if (((int) $resource->getReleaseDate()) && ($resource->getReleaseDate() > time())) {
                                                    $resource_array["release_date"] =  "This textbook reading resource will become accessible <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseDate())."</strong>.";
                                                } elseif (((int) $resource->getReleaseUntil()) && ($resource->getReleaseUntil() < time())) {
                                                    $resource_array["release_until"] = "This textbook reading resource was only available until <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseUntil())."</strong>.";
                                                }
                                            }
                                            break;
                                        case 3 :
                                        case 7 :
                                            $resource = Models_Event_Resource_Link::fetchRowByID($entity->getEntityValue());
                                            if ($resource && is_object($resource)) {

                                                $resource_array = array(
                                                    "entity_id" => $entity->getID(),
                                                    "resource_id" => $resource->getID(),
                                                    "title" => ($resource->getLinkTitle() != "" ? $resource->getLinkTitle() : $resource->getLink()),
                                                    "link" => $resource->getLink(),
                                                    "url" => ENTRADA_RELATIVE . "/link-event.php?id=" . (int) $resource->getID(),
                                                    "description" => $resource->getLinkNotes(),
                                                    "proxyify" => $resource->getProxify(),
                                                    "timeframe" => $resource->getTimeframe(),
                                                    "required" => $resource->getRequired(),
                                                    "resource_type" => $entity->getEntityType(),
                                                    "resource_type_title" => "External Link"
                                                );

                                                $resource_array["release_date"] = "";
                                                $resource_array["release_until"] = "";
                                                if (((int) $resource->getReleaseDate()) && ($resource->getReleaseDate() > time())) {
                                                    $resource_array["release_date"] =  "This link will become accessible <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseDate())."</strong>.";
                                                } elseif (((int) $resource->getReleaseUntil()) && ($resource->getReleaseUntil() < time())) {
                                                    $resource_array["release_until"] = "This link was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseUntil())."</strong>.";
                                                }
                                            }
                                            break;
                                        case 10 :
                                            $resource = Models_Event_Resource_LtiProvider::fetchRowByID($entity->getEntityValue());
                                            if ($resource && is_object($resource)) {
                                                $resource_array = array(
                                                    "entity_id" => $entity->getID(),
                                                    "resource_id" => $resource->getID(),
                                                    "title" => ($resource->getLtiTitle() != "" ? $resource->getLtiTitle() : $resource->getLaunchUrl()),
                                                    "link" => $resource->getLaunchUrl(),
                                                    "description" => $resource->getLtiNotes(),
                                                    "lti_key" => $resource->getLtiKey(),
                                                    "lti_secret" => $resource->getLtiSecret(),
                                                    "lti_params" => $resource->getLtiParams(),
                                                    "timeframe" => $resource->getTimeframe(),
                                                    "required" => $resource->getRequired(),
                                                    "resource_type" => $entity->getEntityType(),
                                                    "resource_type_title" => "LTI Provider"
                                                );

                                                $resource_array["release_date"] = "";
                                                $resource_array["release_until"] = "";
                                                if (((int) $resource->getReleaseDate()) && ($resource->getReleaseDate() > time())) {
                                                    $resource_array["release_date"] =  "This LTI provider will be available for downloading <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseDate())."</strong>.";
                                                } elseif (((int) $resource->getReleaseUntil()) && ($resource->getReleaseUntil() < time())) {
                                                    $resource_array["release_until"] = "This LTI provider was only available for download until <strong>".date(DEFAULT_DATE_FORMAT, $resource->getReleaseUntil())."</strong>.";
                                                }
                                            }
                                            break;
                                        case 12 :
                                            $resource = $entity->getResource();
                                            if ($resource && is_object($resource)) {
                                                $event          = Models_Event::fetchRowByID($PROCESSED["event_id"]);
                                                $EXAM_TEXT      = $translate->_("exams");
                                                $post_view      = new Views_Exam_Post($resource, $event);
                                                $edit           = true;
                                                $post_resources = $post_view->renderEventResource($edit);

                                                $progress       = Models_Exam_Progress::fetchAllByPostID($resource->getID());
                                                if ($progress && is_array($progress)) {
                                                    $progress_count = count($progress);
                                                } else {
                                                    $progress_count = 0;
                                                }

                                                $resource_array = array(
                                                    "entity_id"             => $entity->getID(),
                                                    "resource_id"           => $resource->getID(),
                                                    "title"                 => $post_resources["title"],
                                                    "description"           => $post_resources["description"],
                                                    "timeframe"             => $resource->getTimeFrame(),
                                                    "hidden"                => $resource->getHideExam(),
                                                    "required"              => $resource->getMandatory(),
                                                    "resource_type"         => $entity->getEntityType(),
                                                    "resource_type_title"   => $EXAM_TEXT["title_singular"],
                                                    "available"             => $post_resources["available"],
                                                    "attempts_allowed"      => $post_resources["attempts_allowed"],
                                                    "time_limit"            => $post_resources["time_limit"],
                                                    "post_text"             => $EXAM_TEXT["posts"],
                                                    "delete"                => ($progress ? 0 : 1),
                                                    "progress_count"        => ($progress_count ? $progress_count : 0)
                                                );
                                            }
                                            break;
                                    }
                                    if ($resource) {
                                        $entities[$resource->getTimeframe()][] = $resource_array;
                                    }
                                }
                                echo json_encode(array("status" => "success", "data" => $entities));
                            } else {
                                echo json_encode(array("status" => "error", "data" => "No Event resource are attached to this event."));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "copyright" :
                        $copyright = "No copyright statement available";
                        if ((array_count_values($copyright_settings = (array) $translate->_("copyright")) > 1) && isset($copyright_settings["copyright-uploads"]) && strlen($copyright_settings["copyright-uploads"])) {
                            $copyright = $copyright_settings["copyright-uploads"];
                        }
                        echo json_encode(array("status" => "success", "data" => array("app_name" => APPLICATION_NAME, "copyright_statement" => $copyright)));
                        break;
                    case "resource-views" :
                        $resource_views = false;

                        if (isset(${$request_var}["resource_type"]) && $tmp_input = clean_input(${$request_var}["resource_type"], array("trim", "int"))) {
                            $PROCESSED["resource_type"] = $tmp_input;
                        } else {
                            add_error("No resource type provided.");
                        }

                        if (isset(${$request_var}["resource_id"]) && $tmp_input = clean_input(${$request_var}["resource_id"], array("trim", "int"))) {
                            $PROCESSED["resource_id"] = $tmp_input;
                        } else {
                            add_error("No resource type provided.");
                        }

                        switch ($PROCESSED["resource_type"]) {
                            case 1 :
                            case 5 :
                            case 6 :
                            case 11 :
                                $resource_views = Models_Statistic::getEventFileViews($PROCESSED["resource_id"]);
                                break;
                            case 3 :
                            case 7 :
                                $resource_views = Models_Statistic::getEventLinkViews($PROCESSED["resource_id"]);
                                break;
                        }

                        if ($resource_views) {
                            $resource_views_data = array();
                            foreach ($resource_views as $resource_view) {
                                $resource_views_data[] = array("name" => $resource_view["lastname"] . " " . $resource_view["firstname"], "last_viewed" => date(DEFAULT_DATE_FORMAT, $resource_view["last_viewed_time"]), "views" => $resource_view["views"]);
                            }

                            if ($resource_views_data) {
                                echo json_encode(array("status" => "success", "data" => $resource_views_data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array("This resource has not been viewed by any learners.")));
                            }

                        } else {
                            echo json_encode(array("status" => "error", "data" => array("There are no views associated with this resource.")));
                        }
                        break;
                }
                break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}
