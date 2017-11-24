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
 * A model to handle leave tracking
 *
 * @author Organisation: Queen's University
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Leave_Tracking extends Models_Base {
    protected $leave_id, $proxy_id, $start_date, $end_date, $days_used, $weekdays_used = null, $weekend_days_used = null, $comments, $type_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_leave_tracking";
    protected static $primary_key = "leave_id";
    protected static $default_sort_column = "start_date";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->leave_id;
    }

    public function getLeaveID() {
        return $this->leave_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getTypeID() {
        return $this->type_id;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }
    
    public function getComments() {
        return $this->comments;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getDaysUsed() {
        return $this->days_used;
    }

    public function getWeekdaysUsed() {
        return $this->weekdays_used;
    }

    public function getWeekendDaysUsed() {
        return $this->weekend_days_used;
    }

    public static function fetchRowByID($leave_id, $created_by = NULL, $deleted_date = NULL) {
        $self = new self();

        $params = array(
            array("key" => "leave_id", "value" => $leave_id, "method" => "="),
            array("key" => "deleted_date", "value" => $deleted_date, "method" => "IS")
        );

        if (!is_null($created_by)) {
            $params[] = array("key" => "created_by", "value" => $created_by, "method" => "=");
        }

        return $self->fetchRow(
            $params
        );
    }

    public static function fetchAllGroupedByProxyID($created_by = NULL) {
        global $db;

        $output = false;

        $query = "SELECT * FROM `cbl_leave_tracking` WHERE `deleted_date` IS NULL ".(!is_null($created_by) ? " AND `created_by` = " . $db->qstr($created_by) : "")." GROUP BY `proxy_id`";
        $results = $db->GetAll($query);
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        return $output;
    }

    public static function fetchAllByProxyID($proxy_id, $start_date = NULL, $end_date = NULL, $deleted_date = NULL) {
        global $db;
        $output = false;
        $constraints = array($proxy_id);
        $query = "  SELECT *
                    FROM `cbl_leave_tracking`
                    WHERE `proxy_id` = ? ";
        if (!is_null($start_date) && !is_null($end_date)) {
            $query .= "AND (
                        (`start_date` >= ? AND `end_date` <= ?) OR
                        (`start_date` >= ? AND `end_date` >= ?) OR
                        (`start_date` <= ? AND (`end_date` <= ? AND `end_date` >= ?)) OR
                        (`start_date` <= ? AND `end_date` >= ?)
                        )";
            $constraints = array($proxy_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $start_date, $end_date);
        }
        if (!is_null($deleted_date)) {
            $query .= " AND `deleted_date` <= ".$db->qstr($deleted_date);
        } else {
            $query .= " AND `deleted_date` IS NULL";
        }
        $query .= " ORDER BY `start_date` DESC, `end_date` DESC";
        $results = $db->GetAll($query, $constraints);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self ($result);
            }
        }
        return $output;
    }

    public static function fetchLeaveDayTotalByProxyID($proxy_id) {
        global $db;
        
        $query = "SELECT SUM(`days_used`) AS `leave_days`
                    FROM `cbl_leave_tracking`
                    WHERE `proxy_id` = ? AND `deleted_date` IS NULL";
        return ($db->GetOne($query, array($proxy_id)));
    }

    public static function fetchAllBySearchTerm($search_term) {
        global $db;
        $query = "SELECT a.`id` AS `proxy_id`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    WHERE `firstname` LIKE ('%".$search_term."%')
                    OR `lastname` LIKE ('%".$search_term."%')
                    OR CONCAT(`firstname`, ' ', `lastname`) LIKE ('%".$search_term."%')
                    GROUP BY a.`id`";
        return $db->GetAll($query);
    }

    public static function fetchAllByMyCourses($proxy_id, $organisation_id, $search_term = NULL, $mode = 'default', $year = NULL) {
        global $db;

        $output = false;

        $query = "SELECT a.`proxy_id`
                    FROM `group_members` AS a
                    JOIN `course_audience` AS b
                    ON a.`group_id` = b.`audience_value`
                    AND b.`audience_type` = 'group_id'
                    JOIN `courses` AS c
                    ON b.`course_id` = c.`course_id`
                    LEFT JOIN `course_contacts` AS d
                    ON c.`course_id` = d.`course_id`
                    LEFT JOIN `cbl_leave_tracking` AS e
                    ON e.`proxy_id` = a.`proxy_id`
                    JOIN `".AUTH_DATABASE."`.`user_data` AS f
                    ON a.`proxy_id` = f.`id`

                    WHERE
                    ((
                        (
                            d.`contact_type` = 'director'
                            OR d.`contact_type` = 'ccoordinator'
                            OR d.`contact_type` = 'pcoordinator'
                        ) AND d.`proxy_id` = ?) OR
                    c.`pcoord_id` = ?)
                    AND c.`organisation_id` = ".$db->qstr($organisation_id)."
                    AND a.`member_active` = '1'
                    ".(!is_null($search_term) ? " AND CONCAT(f.`firstname`, ' ', f.`lastname`) LIKE(".$db->qstr("%".$search_term."%").") " : "")."
                    GROUP BY a.`proxy_id`

                    UNION

                    SELECT a.`audience_value` AS `proxy_id` FROM `course_audience` AS a
                    JOIN `courses` AS b
                    ON a.`course_id` = b.`course_id`
                    LEFT JOIN `course_contacts` AS c
                    ON a.`course_id` = c.`course_id`
                    LEFT JOIN `cbl_leave_tracking` AS d
                    ON d.`proxy_id` = a.`audience_value`
                    JOIN `".AUTH_DATABASE."`.`user_data` AS e
                    ON a.`audience_value` = e.`id`
                    WHERE
                    ((
                        (
                            c.`contact_type` = 'director'
                            OR c.`contact_type` = 'ccoordinator'
                            OR c.`contact_type` = 'pcoordinator'
                        ) AND c.`proxy_id` = ?) OR
                    b.`pcoord_id` = ?)
                    AND b.`organisation_id` = ".$db->qstr($organisation_id)."
                    ".(!is_null($search_term) ? " AND CONCAT(e.`firstname`, ' ', e.`lastname`) LIKE(".$db->qstr("%".$search_term."%").") " : "")."

                    GROUP BY `proxy_id`";
        $results = $db->GetAll($query, array($proxy_id, $proxy_id, $proxy_id, $proxy_id));

        if ($results) {
            if ($mode == "default") {
                $output = array();
                foreach ($results as $result) {
                    $tracked_leave = self::fetchAllByProxyID($result["proxy_id"]);
                    if ($tracked_leave) {
                        $output[] = $tracked_leave[0];
                    }
                }

                return $output;
            } elseif ($mode == "search") {
                return $results;
            }
        }

        return $output;
    }

    public static function fetchAllByAssociatedLearnerFacultyProxyList($current_date) {
        global $db;

        $USER_ID_LIST = Entrada_Utilities_Assessments_AssessmentTask::getAssociatedLearnerFacultyProxyList();
        if ($USER_ID_LIST) {
            $query = "  SELECT CONCAT(b.`firstname`, ' ', b.`lastname`) AS 'full_name', a.`days_used`, a.`start_date`, a.`end_date`, a.`proxy_id`, c.`type_value` as `leave_type`
                        FROM `cbl_leave_tracking` AS a
                        JOIN `".AUTH_DATABASE."`.`user_data` AS b
                        ON a.`proxy_id` = b.`id`  
                        JOIN `cbl_lu_leave_tracking_types` AS c
                        ON c.`type_id` = a.`type_id`
                        WHERE a.`deleted_date` IS NULL
                        AND ((? BETWEEN a.`start_date` AND a.`end_date`) OR a.`start_date` >= ?)
                        AND a.`proxy_id` IN ({$USER_ID_LIST})
                        ORDER BY a.`start_date`";

            $results = $db->GetAll($query, array($current_date, $current_date));
            if ($results) {
                foreach ($results as $key => $result) {
                    $results[$key]["url"] = ENTRADA_URL . "/admin/rotationschedule/leave?section=user&proxy_id=" . $result["proxy_id"];
                }
            }
            return $results ? $results : array();
        }
        return array();
    }
}