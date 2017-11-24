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
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Schedule_Draft extends Models_Base {

    protected $cbl_schedule_draft_id, $draft_title, $status, $course_id, $cperiod_id, $created_date, $created_by, $deleted_date, $updated_date, $updated_by;

    protected static $table_name = "cbl_schedule_drafts";
    protected static $primary_key = "cbl_schedule_draft_id";
    protected static $default_sort_column = "draft_title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cbl_schedule_draft_id;
    }

    public function getTitle() {
        return $this->draft_title;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCPeriodID() {
        return $this->cperiod_id;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getScheduleTable() {
        $schedule_data = false;

        $audience_proxy_ids = array();

        $course = Models_Course::fetchRowByID($this->course_id);
        if ($course) {

            $schedule_data = array();
            $schedule_data["unscheduled_on_service_audience"] = array();
            $schedule_data["on_service_audience"] = array();
            $schedule_data["off_service_audience"] = array();
            $schedule_data["blocks"] = array();

            $audience = $course->getAudience($this->cperiod_id);

            foreach ($audience as $audience_member) {
                if ($audience_member->getAudienceType() == "group_id") {
                    $group_members = $audience_member->getMembers();
                    if ($group_members) {
                        foreach ($group_members as $member) {
                            $audience_proxy_ids[$member->getID()]["proxy_id"] = $member->getID();
                            $audience_proxy_ids[$member->getID()]["number"] = get_account_data("number", $member->getID());
                        }
                    }
                } else {
                    $audience_proxy_ids[$audience_member->getAudienceValue()]["proxy_id"] = $audience_member->getAudienceValue();
                    $audience_proxy_ids[$audience_member->getAudienceValue()]["number"] = get_account_data("number", $audience_member->getAudienceValue());
                }
            }

            $course_codes = array();
            foreach ($audience_proxy_ids as $audience_member) {

                $member_slots = Models_Schedule_Audience::fetchAllByProxyIDDraftID($audience_member["proxy_id"], $this->cbl_schedule_draft_id);

                if ($member_slots) {
                    foreach ($member_slots as $slot) {
                        if (!array_key_exists($slot["draft_id"], $course_codes)) {
                            $course = Models_Course::fetchRowByID($slot["course_id"]);
                            $course_codes[$slot["draft_id"]] = strtoupper($course->getCourseCode());
                        }
                        
                        $slot_schedule = Models_Schedule::fetchRowByID($slot["schedule_id"]);
                        if ($slot_schedule) {

                            $audience_proxy_ids[$audience_member["proxy_id"]]["slots"][$slot_schedule->getBlockTypeID()][$slot["schedule_order"]][] = array(
                                "slot_id" => $slot["schedule_slot_id"],
                                "schedule_id" => $slot["schedule_id"],
                                "start_date" => $slot_schedule->getStartDate(),
                                "end_date" => $slot_schedule->getEndDate(),
                                "saudience_id" => $slot["saudience_id"],
                                "title" => $slot["title"],
                                "code" => strtoupper($this->getID() == $slot["draft_id"] ? $slot["code"] : $course_codes[$slot["draft_id"]] . "-" . $slot["code"]),
                                "slot_type_id" => $slot["slot_type_id"]
                            );
                        }
                    }
                }
            }

            $distinct_block_types = Models_Schedule::fetchDistinctBlockTypesByDraftID($this->cbl_schedule_draft_id);
            if ($distinct_block_types) {
                $schedule_data["block_types"] = array();
                foreach ($distinct_block_types as $block_type_id) {
                    $block_type = Models_BlockType::fetchRowByID($block_type_id["block_type_id"]);
                    if ($block_type) {
                        $schedule_data["block_types"][] = $block_type;
                    }
                }
            }

            $schedules = Models_Schedule::fetchAllByDraftID($this->cbl_schedule_draft_id);
            if ($schedules) {

                foreach ($schedules as $schedule) {
                    if ($schedule->getScheduleType() == "rotation_stream") {
                        $schedule_data["schedules"][$schedule->getID()]["schedule_name"] = $schedule->getTitle();
                        $schedule_data["schedules"][$schedule->getID()]["schedule_code"] = strtoupper($schedule->getCode());
                    } else {
                        $schedule_data["blocks"][$schedule->getBlockTypeID()][$schedule->getOrder()][] = array (
                            "schedule_id" => $schedule->getID(),
                            "start_date" => $schedule->getStartDate(),
                            "end_date" => $schedule->getEndDate()
                    );

                        $schedule_data["schedules"][$schedule->getScheduleParentID()]["children"][] = $schedule->getID();
                    }
                }

                foreach ($audience_proxy_ids as $proxy_id => $member) {
                    $u = User::fetchRowByID($proxy_id);
                    if ($u) {
                        $member["name"] = $u->getFullname();
                        $member["user_data"] = $u->toArray();
                    }

                    if (!isset($member["slots"]) || empty($member["slots"])) {
                        $schedule_data["unscheduled_on_service_audience"][$member["proxy_id"]] = $member;
                    } else {
                        $schedule_data["on_service_audience"][$member["proxy_id"]] = $member;
                    }
                }

                $off_service_audience = Models_Schedule_Audience::fetchAllOffService($this->cbl_schedule_draft_id);
                if ($off_service_audience) {
                    foreach ($off_service_audience as $slot) {
                        if (!array_key_exists($slot["audience_value"], $audience_proxy_ids)) {
                            $u = User::fetchRowByID($slot["audience_value"]);
                            if ($u) {
                                $schedule_data["off_service_audience"][$member["audience_value"]]["proxy_id"] = $member["audience_value"];
                                $schedule_data["off_service_audience"][$member["audience_value"]]["name"] = $u->getFullname();
                                $schedule_data["off_service_audience"][$member["audience_value"]]["slots"][$slot["schedule_order"]] = $slot;
                                $schedule_data["off_service_audience"][$member["audience_value"]]["user_data"] = $u->toArray();
                            }
                        }
                    }
                }
            } else {
                $schedule_data = false;
            }
        }

        if ($schedule_data) {
            ksort($schedule_data["blocks"]);
            return $schedule_data;
        } else {
            return false;
        }

    }

    public static function fetchRowByID($cbl_schedule_draft_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cbl_schedule_draft_id", "value" => $cbl_schedule_draft_id, "method" => "=")
        ));
    }

    public static function fetchAllByProxyID($proxy_id, $status = "draft") {
        global $db;

        $drafts = false;

        $query = "  SELECT a.*
                    FROM `cbl_schedule_drafts` AS a
                    JOIN `cbl_schedule_draft_authors` AS b
                    ON a.`cbl_schedule_draft_id` = b.`cbl_schedule_draft_id`
                    WHERE b.`proxy_id` = ?
                    AND a.`status` = ?
                    AND a.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($proxy_id, $status));
        if ($results) {
            $drafts = array();
            foreach ($results as $result) {
                $drafts[] = new self($result);
            }
        }
        return $drafts;
    }

    public static function fetchAllLiveDraftsByOrg($organisation_id) {
        global $db;
        $query = "SELECT a.*
                    FROM `cbl_schedule_drafts` AS a
                    JOIN `courses` AS b
                    ON a.`course_id` = b.`course_id`
                    WHERE a.`status` = 'live'
                    AND b.`organisation_id` = ?";
        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array(
                "key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")
            )
        ));
    }
    
    public static function fetchAllByCPeriodID($cperiod_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
           array("key" => "cperiod_id", "value" => $cperiod_id, "method" => "="),
           array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

}