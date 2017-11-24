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
 * @author Organization: Queen's University
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

class Models_Event_Audience extends Models_Base {
    protected $eaudience_id,
                $event_id,
                $audience_name,
                $audience_type,
                $audience_value,
                $custom_time,
                $custom_time_start,
                $custom_time_end,
                $updated_date,
                $updated_by;

    protected static $table_name           = "event_audience";
    protected static $primary_key          = "eaudience_id";
    protected static $default_sort_column  = "eaudience_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->eaudience_id;
    }

    public function getEventAudienceID() {
        return $this->eaudience_id;
    }

    public function getEventID() {
        return $this->event_id;
    }

    public function getAudienceType() {
        return $this->audience_type;
    }

    public function getAudienceValue() {
        return $this->audience_value;
    }

    public function getCustomTime() {
        return $this->custom_time;
    }

    public function getCustomTimeStart() {
        return $this->custom_time_start;
    }

    public function getCustomTimeEnd() {
        return $this->custom_time_end;
    }

    public function setEventId($event_id) {
        $this->event_id = $event_id;
    }

    public function setUpdatedBy($id) {
        $this->updated_by = $id;
    }

    public function setUpdatedDate($time) {
        $this->updated_date = $time;
    }

    public function setCustomTime($custom_time) {
        $this->custom_time = $custom_time;
    }

    public function setCustomTimeStart($custom_time_start) {
        $this->custom_time_start = $custom_time_start;
    }

    public function setCustomTimeEnd($custom_time_end) {
        $this->custom_time_end = $custom_time_end;
    }

    public function getAudienceName() {
        if (NULL === $this->audience_name) {
            $audience_value = $this->audience_value;
            $audience_type  = $this->audience_type;
            switch ($audience_type) {
                case "cohort" :
                    $cohort = Models_Group::fetchRowByID($audience_value);
                    if ($cohort) {
                        $this->audience_name = $cohort->getGroupName();
                    }
                break;
                case "group_id" :
                    $cgroup = Models_Course_Group::fetchRowByID($audience_value);
                    if ($cgroup) {
                        $this->audience_name = $cgroup->getGroupName();
                    }
                    break;
                case "proxy_id" :
                    $student = User::fetchRowByID($audience_value);
                    if ($student) {
                        $this->audience_name = $student->getFullname();
                    }
                    break;
            }
        }

        return $this->audience_name;
    }

    /* @return bool|Models_Event_Audience */
    public static function fetchRowByID($eaudience_id = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eaudience_id", "value" => $eaudience_id, "method" => "=")
        ));
    }
    
    /* @return bool|Models_Event_Audience */
    public static function fetchRowByEventID($event_id = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Event_Audience */
    public static function fetchRowByEventIdTypeValue($event_id, $event_type, $event_value) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "audience_type", "value" => $event_type, "method" => "="),
            array("key" => "audience_value", "value" => $event_value, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Event_Audience[] */
    public static function fetchAllByEventIdType($event_id, $audience_type) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "audience_type", "value" => $audience_type, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Event_Audience[] */
    public static function fetchAllByEventID($event_id = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "event_id", "value" => $event_id, "method" => "=")
        ));
    }

    public function delete() {
        global $db;
        $sql = "DELETE FROM `" . static::$table_name . "`
                WHERE `" . static::$primary_key . "` = " . $db->qstr($this->getID());

        if ($db->Execute($sql)) {
            return true;
        } else {
            application_log("error", "Error deleting  ".get_called_class()." id[" . $this->getID() . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }

    /**
     * This method searches the audience of the specified event to see if the proxy_id
     * provided should be an active audience member.
     *
     * @param $proxy_id
     * @param $event_id
     * @param int $event_start
     * @return bool
     */
    public function isAudienceMember($proxy_id, $event_id, $event_start = 0) {
        $audience = array();

        $event_audience = $this->fetchAllByEventID($event_id);
        if ($event_audience) {
            foreach ($event_audience as $event) {
                $a = $event->getAudience($event_start);

                $members = $a->getAudienceMembers();
                if ($members) {
                    foreach ($members as $member) {
                        $audience[] = $member["id"];
                    }
                }
            }

            if ($audience && in_array($proxy_id, $audience)) {
                return true;
            }
        }

        return false;
    }

    public function getAudience($event_start = 0, $search_query = "") {
        global $db;

        $audience = false;

        $event_start = (int) $event_start;

        switch ($this->audience_type) {
            case "proxy_id" :
                $query = "SELECT `id`, `firstname`, `lastname` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ? AND (`firstname` LIKE ?
										OR `lastname` LIKE ?)";
                $result = $db->GetRow($query, array($this->audience_value, "%".$search_query."%", "%".$search_query."%"));
                if ($result) {
                    $audience_data["audience_name"] = $result["firstname"] . " " . $result["lastname"];
                    $audience_data["audience_type"] = $this->audience_type;
                    $audience_data["audience_members"][$result["id"]] = $result;
                    $audience = new Models_Audience($audience_data);
                }
                break;
            case "cohort" :
                $query = "SELECT `group_id`, `group_name` FROM `groups` WHERE `group_id` = ?";
                $result = $db->GetRow($query, array($this->audience_value));
                $members = array();
                if ($result) {
                    $audience_data["audience_name"] = $result["group_name"];
                    $audience_data["audience_type"] = $this->audience_type;
                    $query = "SELECT b.`id`, b.`firstname`, b.`lastname`
                                FROM `group_members` AS a
                                JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                ON a.`proxy_id` = b.`id`
                                WHERE a.`group_id` = ?
                                AND a.`member_active` = '1'
                                AND (b.`firstname` LIKE ? OR b.`lastname` LIKE ?)";
                    $results = $db->GetAll($query, array($this->audience_value, "%".$search_query."%", "%".$search_query."%"));
                    if ($results && is_array($results)) {
                        foreach ($results as $proxy_id => $result) {
                            $members[$result["id"]] = $result;
                        }
                    }

                    $audience_data["audience_members"] = $members;
                    if (!empty($audience_data)) {
                        $audience = new Models_Audience($audience_data);
                    }
                }
                break;
            case "group_id" :
                $query = "SELECT `cgroup_id`, `group_name` FROM `course_groups` WHERE `cgroup_id` = ?";
                $result = $db->GetRow($query, array($this->audience_value));

                $members = array();
                if ($result) {
                    $audience_data["audience_name"] = $result["group_name"];
                    $audience_data["audience_type"] = $this->audience_type;
                    $query = "SELECT b.`id`, b.`firstname`, b.`lastname`
                                FROM `course_group_audience` AS a
                                JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                ON a.`proxy_id` = b.`id`
                                WHERE a.`cgroup_id` = ?
                                AND a.`active` = '1'
                                AND (b.`firstname` LIKE ? OR b.`lastname` LIKE ?)";
                    $results = $db->GetAll($query, array($this->audience_value, "%".$search_query."%", "%".$search_query."%"));

                    if ($results && is_array($results)) {
                        foreach ($results as $proxy_id => $result) {
                            $members[$result["id"]] = $result;
                        }
                    }

                    $audience_data["audience_members"] = $members;
                    if (!empty($audience_data)) {
                        $audience = new Models_Audience($audience_data);
                    }
                }
                break;
            case "course_id" :
                $query = "SELECT *
                            FROM `course_audience` AS a
                            JOIN `curriculum_periods` AS b
                            ON a.`cperiod_id` = b.`cperiod_id`
                            WHERE a.`course_id` = ?
                            AND (? BETWEEN b.`start_date` AND b.`finish_date`)
                            AND b.`active` = '1'";
                $course_audiences = $db->GetAll($query, array($this->audience_value, $event_start));
                if ($course_audiences) {
                    $query = "SELECT `course_name` FROM `courses` WHERE `course_id` = ?";
                    $result = $db->GetRow($query, array($this->audience_value));
                    if ($result) {
                        $audience_data["audience_name"] = $result["course_name"];
                        $audience_data["audience_type"] = $this->audience_type;
                    }

                    $members = array();
                    foreach ($course_audiences as $course_audience) {
                        if ($course_audience && $course_audience["audience_type"] == "group_id") {
                            $query = "SELECT b.`id`, b.`firstname`, b.`lastname`
                                        FROM `group_members` AS a
                                        JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                        ON a.`proxy_id` = b.`id`
                                        WHERE a.`group_id` = ?
                                        AND a.`member_active` = '1'
                                        AND (b.`firstname` LIKE ? OR b.`lastname` LIKE ?)";
                            $results = $db->GetAll($query, array($course_audience["audience_value"], "%".$search_query."%", "%".$search_query."%"));
                            if ($results) {
                                foreach ($results as $proxy_id => $result) {
                                    $members[$result["id"]] = $result;
                                }
                            }
                        }
                    }

                    $audience_data["audience_members"] = $members;

                    if (!empty($audience_data)) {
                        $audience = new Models_Audience($audience_data);
                    }
                }
                break;
            default:
                continue;
                break;
        }

        return $audience;
    }

    public static function buildAudienceMembers($audiences) {
        $audience_members = array();
        if (isset($audiences) && is_array($audiences) && !empty($audiences)) {
            foreach ($audiences as $audience) {
                if ($audience && is_object($audience)) {
                    $audience_array = $audience->toArray();
                    if (isset($audience_array["audience_members"]) && is_array($audience_array["audience_members"]) && !empty($audience_array["audience_members"])) {
                        foreach ($audience_array["audience_members"] as $proxy => $member) {
                            if (!in_array($proxy, $audience_members)) {
                                $audience_members[] = $proxy;
                            }
                        }
                    }
                }
            }
        }

        return $audience_members;
    }


    public static function onlyCourse($audiences) {
        $course_exist = false;
        if ($audiences && is_array($audiences) && !empty($audiences)) {
            foreach ($audiences as $audience) {
                if ($audience && is_object($audience)) {
                    if ($audience->getAudienceType() == "course_id") {
                        $course_exist = true;
                    }
                }
            }
        }
        return $course_exist;
    }
}