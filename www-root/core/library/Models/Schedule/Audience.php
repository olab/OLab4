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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Schedule_Audience extends Models_Base {

    protected $saudience_id, $one45_p_id, $schedule_id, $schedule_slot_id, $audience_type, $audience_value, $deleted_date;

    protected static $table_name = "cbl_schedule_audience";
    protected static $primary_key = "saudience_id";
    protected static $default_sort_column = "saudience_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getAudienceType() {
        return $this->audience_type;
    }

    public function getAudienceValue() {
        return $this->audience_value;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getOne45PID() {
        return $this->one45_p_id;
    }

    public function getScheduleID() {
        return $this->schedule_id;
    }

    public function getScheduleSlotID() {
        return $this->schedule_slot_id;
    }

    public function getSaudienceID() {
        return $this->saudience_id;
    }

    public function getID() {
        return $this->saudience_id;
    }

    public static function fetchRowByID($saudience_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "saudience_id", "value" => $saudience_id, "method" => "=")
        ));
    }

    public static function fetchRowBySlotIDTypeValue($slot_id, $audience_type, $audience_value) {
        global $db;
        $query = "SELECT * FROM `cbl_schedule_audience` WHERE `schedule_slot_id` = ? AND `audience_type` = ? AND `audience_value` = ? AND `deleted_date` IS NULL";
        $result = $db->GetRow($query, array($slot_id, $audience_type, $audience_value));
        if ($result) {
            return new self ($result);
        } else {
            return false;
        }
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "saudience_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllBySlotID($slot_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "schedule_slot_id", "value" => $slot_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public static function fetchAllByScheduleID($schedule_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "schedule_id", "value" => $schedule_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public static function fetchAllByScheduleIdsIn($schedule_ids) {
        global $db;
        $cleaned_ids = array_map(function($value) {
            return clean_input($value, array("int"));
        }, $schedule_ids);
        $id_string = implode(",", $cleaned_ids);

        $grouped = array();

        $query = "SELECT a.`schedule_id`, a.`audience_value`, b.`block_type_id`
                  FROM `cbl_schedule_audience` AS a
                  LEFT JOIN `cbl_schedule` AS b
                  ON b.`schedule_id` = a.`schedule_id`
                  WHERE a.`audience_type` = 'proxy_id'
                  AND   a.`schedule_id` IN($id_string)";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $audience) {
                $grouped[$audience["block_type_id"]][$audience["audience_value"]][] = $audience["schedule_id"];
            }
        }
        return $grouped;
    }

    public static function fetchAllByProxyID($proxy_id) {
        global $db;
        $query = "SELECT a.*, b.`schedule_order`, c.`slot_type_id`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule` AS b
                    ON  a.`schedule_id` = b.`schedule_id`
                    JOIN `cbl_schedule_slots` AS c
                    ON a.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE a.`audience_value` = ?
                    AND a.`deleted_date` IS NULL
                    ORDER BY b.`start_date`";
        $results = $db->getAll($query, array($proxy_id));
        return $results;
    }

    public static function fetchAllByProxyIDDraftID($proxy_id, $draft_id) {
        global $db;
        $query = "SELECT `saudience_id`, a.`schedule_id`, a.`schedule_slot_id`, a.`audience_type`, a.`audience_value`, b.`slot_type_id`, d.`code`, d.`title`, c.`schedule_order`, c.`draft_id`, c.`course_id`, c.`block_type_id`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule_slots` AS b
                    ON a.`schedule_slot_id` = b.`schedule_slot_id`
                    JOIN `cbl_schedule` AS c
                    ON a.`schedule_id` = c.`schedule_id`
                    JOIN `cbl_schedule` AS d
                    ON c.`schedule_parent_id` = d.`schedule_id`
                    WHERE a.`audience_value` = ?
                    AND a.`deleted_date` IS NULL
                    AND (c.`start_date` >= d.`start_date`
                    AND c.`end_date` <= d.`end_date`)
                    AND ((c.`draft_id` = ? AND b.`slot_type_id` = '1') OR (c.`draft_id` != ? AND b.`slot_type_id` = '2'))
                    ORDER BY c.`schedule_order`";
        $results = $db->GetAll($query, array($proxy_id, $draft_id, $draft_id));
        return $results;
    }

    public static function fetchAllOffService($draft_id) {
        global $db;
        $query = "SELECT `saudience_id`, a.`schedule_id`, a.`schedule_slot_id`, a.`audience_type`, a.`audience_value`, b.`slot_type_id`, d.`code`, c.`schedule_order`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule_slots` AS b
                    ON a.`schedule_slot_id` = b.`schedule_slot_id`
                    JOIN `cbl_schedule` AS c
                    ON a.`schedule_id` = c.`schedule_id`
                    JOIN `cbl_schedule` AS d
                    ON c.`schedule_parent_id` = d.`schedule_id`
                    WHERE b.`slot_type_id` != 1
                    AND d.`draft_id` = ?
                    AND a.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($draft_id));
        return $results;
    }

    public static function fetchAllOnServiceByDraftID($draft_id_list) {
        global $db;
        $query = "  SELECT DISTINCT a.`audience_value`, a.`audience_type`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule_slots` AS b
                    ON a.`schedule_slot_id` = b.`schedule_slot_id`
                    JOIN `cbl_schedule` AS c
                    ON a.`schedule_id` = c.`schedule_id`
                    WHERE b.`slot_type_id` = 1
                    AND a.`deleted_date` IS NULL
                    AND c.`draft_id` IN ({$draft_id_list})";
        $results = $db->GetAll($query);
        return $results;
    }

    public static function fetchAllOnServiceByScheduleID($schedule_id) {
        global $db;
        $query = "SELECT `saudience_id`, a.`schedule_id`, a.`schedule_slot_id`, a.`audience_type`, a.`audience_value`, b.`slot_type_id`, c.`schedule_order`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule_slots` AS b
                    ON a.`schedule_slot_id` = b.`schedule_slot_id`
                    JOIN `cbl_schedule` AS c
                    ON a.`schedule_id` = c.`schedule_id`
                    WHERE b.`slot_type_id` = 1
                    AND c.`schedule_id` = ?
                    AND a.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($schedule_id));
        return $results;
    }

    public static function fetchAllOffServiceByScheduleID($schedule_id) {
        global $db;
        $query = "SELECT `saudience_id`, a.`schedule_id`, a.`schedule_slot_id`, a.`audience_type`, a.`audience_value`, b.`slot_type_id`, c.`schedule_order`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule_slots` AS b
                    ON a.`schedule_slot_id` = b.`schedule_slot_id`
                    JOIN `cbl_schedule` AS c
                    ON a.`schedule_id` = c.`schedule_id`
                    WHERE b.`slot_type_id` != 1
                    AND c.`schedule_id` = ?
                    AND a.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($schedule_id));
        return $results;
    }

    public static function fetchAvailableAudience($search_value) {
        global $db;

        $query = "SELECT a.`id`, CONCAT(a.`firstname`, ' ', a.`lastname`) AS `fullname`, a.`email`
                    FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                    HAVING `fullname` LIKE (".$db->qstr($search_value).")
                    OR `email` LIKE (".$db->qstr($search_value).")";
        $results = $db->GetAll($query);
        return $results;
    }

    public static function getSlotMembers($course_id, $org_id, $schedule_id = NULL, $search_value = NULL, $on_service = true) {
        global $db;

        $query = "SELECT d.`proxy_id`, f.`category_code`, CONCAT(g.`firstname`, ' ', g.`lastname`) AS `fullname`, g.`email`
                    FROM `courses` AS a
                    JOIN `curriculum_periods` AS b
                    ON a.`curriculum_type_id` = b.`curriculum_type_id`
                    JOIN `course_audience` AS c
                    ON b.`cperiod_id` = c.`cperiod_id`
                    AND a.`course_id` = c.`course_id`
                    AND c.`audience_type` = 'group_id'
                    JOIN `group_members` AS d
                    ON c.`audience_value` = d.`group_id`
                    AND d.`member_active` = '1'
                    LEFT JOIN `".AUTH_DATABASE."`.`user_data_resident` AS e
                    ON d.`proxy_id` = e.`proxy_id`
                    LEFT JOIN `mtd_categories` AS f
                    ON e.`category_id` = f.`id`
                    JOIN `".AUTH_DATABASE."`.`user_data` AS g
                    ON d.`proxy_id` = g.`id`".
                    (!is_null($schedule_id) ? "
                    LEFT JOIN `cbl_schedule_audience` AS h
                    ON d.`proxy_id` = h.`audience_value`
                    AND h.`audience_type` = 'proxy_id'
                    AND h.`schedule_id` = ".$db->qstr($schedule_id) : "") . "
                    WHERE a.`organisation_id` = ? ".
                    ($on_service ? " AND a.`course_id` = ? " : " AND a.`course_id` != ? ").
                    (!is_null($schedule_id) ? "AND h.`saudience_id` IS NULL" : "")."
                    GROUP BY d.`proxy_id`".
                    (!is_null($search_value) ? " HAVING `fullname` LIKE ('%".$search_value."%') " : "")."
                    ORDER BY d.`proxy_id`";

        $results = $db->GetAll($query, array($org_id, $course_id));
        return $results;
    }

    public static function fetchDraftIDCPeriodID($proxy_id, $date = NULL) {
        global $db;
        $query = "SELECT b.`draft_id`, d.`cperiod_id`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_id`
                    JOIN `cbl_schedule_slots` AS c
                    ON a.`schedule_slot_id` = c.`schedule_slot_id`
                    JOIN `curriculum_periods` AS d ON b.`cperiod_id`
                    WHERE a.`audience_type` = 'proxy_id'
                    AND a.`audience_value` = ?
                    AND c.`slot_type_id` = '1'
                    AND (".(is_null($date) ? "UNIX_TIMESTAMP()" : $db->qstr($date))." BETWEEN d.`start_date` AND d.`finish_date`)
                    GROUP BY b.`draft_id`;";
        return $db->GetRow($query, array($proxy_id));
    }

}