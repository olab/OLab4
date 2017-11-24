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
 * API to handle interaction with eportfolio module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

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

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if (!$ENTRADA_ACL->amIAllowed("rotationschedule", "update", false)) {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        $admin = false;
    } else {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            add_error($translate->_("Invalid proxy ID"));
        }
        $admin = true;
    }

    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
        $PROCESSED["draft_id"] = $tmp_input;
    } else {
        add_error($translate->_("Invalid draft ID"));
    }

    if (isset($request["start"]) && $tmp_input = clean_input($request["start"], "strtotime")) {
        $PROCESSED["start_date"] = $tmp_input;
    } else {
        add_error($translate->_("Invalid start date"));
    }

    if (isset($request["end"]) && $tmp_input = clean_input($request["end"], "strtotime")) {
        $PROCESSED["end_date"] = $tmp_input;
    } else {
        add_error($translate->_("Invalid end date"));
    }

    if (!$ERROR) {
        $rotation_schedule_output = array();
        $current_start = "";
        $current_end = "";

        $rotation_schedule_audience_membership = Models_Schedule_Audience::fetchAllByProxyID($PROCESSED["proxy_id"]);
        if ($rotation_schedule_audience_membership) {
            foreach ($rotation_schedule_audience_membership as $audience) {
                $schedule = Models_Schedule::fetchRowByIDStatus($audience["schedule_id"], "live", true);
                if ($schedule) {
                    $draft_ids[] = $schedule->getDraftID();
                }
            }
        }
        if ($admin == true || !is_array($draft_ids)) {
            $schedules = Models_Schedule::fetchRotationsByMonth($PROCESSED["proxy_id"], $PROCESSED["draft_id"], $PROCESSED["start_date"], $PROCESSED["end_date"]);
            if ($schedules) {
                foreach ($schedules as $schedule) {
                    $schedule["start"] = date("Y-m-d", $schedule["start"]);
                    $schedule["end"] = date("Y-m-d", $schedule["end"]) . " 23:59:59";
                    $rotation_schedule_output[] = $schedule;
                }
            }
        } else {
            if ($draft_ids) {
                $unique_draft_ids = array_unique($draft_ids);
                foreach ($unique_draft_ids as $draft_id) {
                    $schedules_draft = Models_Schedule::fetchAllByDraftID($draft_id, "rotation_stream");
                    if ($schedules_draft) {
                        $past_start = array();
                        $cperiod_id = $schedules_draft[0]->getCPeriodID();
                        $cperiod = Models_Curriculum_Period::fetchRowByID($cperiod_id);
                        $schedules = Models_Schedule::fetchRotationsByMonth($ENTRADA_USER->getActiveID(), $draft_id, $PROCESSED["start_date"], $PROCESSED["end_date"]);
                        foreach ($schedules as $schedule) {
                            $schedule["start"] = date("Y-m-d", $schedule["start"]);
                            $schedule["end"] = date("Y-m-d", $schedule["end"]) . " 23:59:59";
                            if (!in_array($schedule["start"], $past_start)){
                                $rotation_schedule_output[] = $schedule;
                                $past_start[] = $schedule["start"];
                                $past_end = $schedule["end"];
                            }
                        }
                    }
                }
            }
        }

        $leave_colours = array(
            "vacation"      => "50ad39",
            "conference"    => "50ad39",
            "interview"     => "50ad39",
            "sick"          => "acad39",
            "other"         => "acad39",
            "maternity"     => "acad39",
            "paternity"     => "5439ad",
            "absence"       => "5439ad",
            "stat days"     => "5439ad",
            "research"      => "8e39ad",
            "education days" => "8e39ad",
            "professional development" => "8e39ad",
            "study days"    => "ad3965",
            "academic half day" => "ad3965"
        );
        $my_leave = Models_Leave_Tracking::fetchAllByProxyID($PROCESSED["proxy_id"], $PROCESSED["start_date"], $PROCESSED["end_date"]);

        if ($my_leave) {
            foreach ($my_leave as $leave) {
                $leave_type = Models_Leave_Type::fetchRowByID($leave->getTypeID());
                $rotation_schedule_output[] = array(
                    "title"         => ucwords($leave_type->getTypeValue()),
                    "start"         => date("Y-m-d", $leave->getStartDate()),
                    "end"           => date("Y-m-d", $leave->getEndDate()) . " 23:59:59",
                    "color"         => "#".$leave_colours[strtolower($leave_type->getTypeValue())],
                    "event_type"    => "leave",
                    "leave_id"      => $leave->getID(),
                    "leave_type"    => $leave_type->getTypeValue(),
                    "days_used"     => $leave->getDaysUsed()
                );
            }
        }

        echo json_encode($rotation_schedule_output);
    }

    exit;

}