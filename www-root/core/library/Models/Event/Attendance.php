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
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Models_Event_Attendance extends Models_Base {

    protected $eattendance_id,
        $event_id,
        $proxy_id,
        $updated_date,
        $updated_by;

    protected static $table_name           = "event_attendance";
    protected static $primary_key          = "eaudience_id";
    protected static $default_sort_column  = "eattendance_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->eattendance_id;
    }

    public function getEventAttendanceID() {
        return $this->eattendance_id;
    }

    public function getEventId() {
        return $this->event_id;
    }

    public function getProxyId() {
        return $this->proxy_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    /**
     * This method toggles a proxy_ids presence in a learning event. It returns "absent" if a user was
     * successfully removed, "present" if a user was successfully added, otherwise returns false.
     *
     * @param $event_id
     * @param $proxy_id
     * @return bool|string
     */
    public static function toggleAttendance($proxy_id, $event_id) {
        global $db, $ENTRADA_USER;

        $event_id = (int) $event_id;
        $proxy_id = (int) $proxy_id;

        $query = "SELECT * FROM `event_attendance` WHERE `event_id` = ? AND `proxy_id` = ?";
        $result = $db->GetRow($query, array($event_id, $proxy_id));
        if ($result) {
            $query = "DELETE FROM `event_attendance` WHERE `event_id` = ? AND `proxy_id` = ?";
            if ($db->Execute($query, array($event_id, $proxy_id))) {
                return "absent";
            }
        } else {
            $attendance_record = array(
                "event_id" => $event_id,
                "proxy_id" => $proxy_id,
                "updated_date" => time(),
                "updated_by" => $ENTRADA_USER->getID()
            );

            if ($db->AutoExecute("event_attendance", $attendance_record, "INSERT")) {
                return "present";
            }
        }

        return false;
    }

    public static function fetchAllByPrimaryKeyByEventID($event_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
        ));
    }

    public static function build_sorter($key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }

    public static function sortAudience($audience) {
        $self = new self();
        uasort($audience, $self->build_sorter('lastname'));
        return $audience;
    }

    public static function fetchAllByEventID($event_id, $event_start = 0, $search_value = "") {
        global $db;

        $event_audience = Models_Event_Audience::fetchAllByEventID($event_id);
        if ($event_audience) {
            foreach ($event_audience as $event) {
                if ($a = $event->getAudience($event_start, $search_value)) {
                    $members = $a->getAudienceMembers();
                    if ($members) {
                        foreach ($members as $member) {
                            $attendance[$member["id"]] = array(
                                "firstname" => $member["firstname"],
                                "lastname" => $member["lastname"],
                                "has_attendance" => false
                            );
                        }
                    }
                }
            }

            if ($attendance) {
                $proxy_ids = array_keys($attendance);

                $query = "SELECT * FROM `event_attendance` WHERE `event_id` = ? AND `proxy_id` IN (" . implode(", ", $proxy_ids) . ") GROUP BY `proxy_id`";
                $results = $db->GetAll($query, array($event_id));
                if ($results) {
                    foreach ($results as $result) {
                        $attendance[$result["proxy_id"]]["has_attendance"] = true;
                    }
                }
            }
        }

        return $attendance;
    }
}
