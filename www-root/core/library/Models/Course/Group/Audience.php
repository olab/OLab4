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
 * A model for handling users in course groups.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Course_Group_Audience extends Models_Base {
    protected $cgaudience_id, $cgroup_id, $proxy_id, $entrada_only = 0, $start_date = 0, $finish_date = 0, $active;

    protected static $table_name = "course_group_audience";
    protected static $primary_key = "cgaudience_id";
    protected static $default_sort_column = "cgaudience_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cgaudience_id;
    }

    public function getCgaudienceID() {
        return $this->cgaudience_id;
    }

    public function getCgroupID() {
        return $this->cgroup_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getEntradaOnly() {
        return $this->entrada_only;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getFinishDate() {
        return $this->finish_date;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = (int) $active;
        return $this;
    }

    public static function fetchRowByID($cgaudience_id, $active) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cgaudience_id", "value" => $cgaudience_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ));
    }

    public static function fetchAllRecords($active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    public static function fetchAllByCGroupID($cgroup_id, $active = 0) {
        $self = new self();

        $constrains = array(
            array("key" => "cgroup_id", "value" => $cgroup_id, "method" => "=")
        );

        if ($active) {
            $constrains[] = array("key" => "active", "value" => $active, "method" => "=");
        }

        return $self->fetchAll($constrains);
    }

    public static function fetchAllByCGroupIDProxyID($cgroup_id, $proxy_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "cgroup_id", "value" => $cgroup_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "=")
        ));
    }

    public function deleteByGroupID($id = 0) {
        global $db;

        $query = "DELETE FROM `course_group_audience` WHERE `cgroup_id`= ?";

        $result = $db->Execute($query, $id);

        if($result) {
            return $db->Affected_Rows();
        }
        return false;
    }

    public function getGroupMembersByGroupID($group_id, $search_term = "", $offset = null, $limit = null, $sort_column = null, $sort_direction = null) {
        global $db;

        $sort_columns_array = array(
            "name" => "`a`.`lastname`",
        );

        $order_sql = " ORDER BY ".$sort_columns_array[$sort_column]. " ".$sort_direction." " ;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND  `a`.`lastname` LIKE (". $db->qstr($search_term) . ") OR `a`.`firstname` LIKE (". $db->qstr($search_term) . ")";
        }


        $query	= "	SELECT c.`cgaudience_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, c.`active`,
                    a.`username`, a.`organisation_id`, a.`username`, CONCAT_WS(':', b.`group`, b.`role`) AS `grouprole`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    INNER JOIN `course_group_audience` AS c 
                    ON a.`id` = c.`proxy_id`
                    WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
					 " . $search_sql . "
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ? )
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ? )
                    AND c.`active` = 1
                    AND c.`cgroup_id` = ?
                    GROUP BY a.`id`
                     " . $order_sql . "
                     LIMIT ?, ? ";

        $results = $db->GetAll($query, array(time(), time(), $group_id, $offset, $limit));

        if ($results) {
            return $results;
        }
        return false;
    }

    public function getTotalGroupMembersByGroupID($group_id, $search_term = "") {
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND  `course_group_audience`.`lastname` LIKE (". $db->qstr($search_term) . ") OR `course_group_audience`.`firstname` LIKE (". $db->qstr($search_term) . ")";
        }

        $query = "	SELECT COUNT(*) AS `total_rows` FROM `course_group_audience` WHERE `active` = 1 AND `cgroup_id` = ? " . $search_sql."";
        $results = $db->GetRow($query, array($group_id));

        if ($results) {
            return $results;
        }
        return false;

    }

    public function getAllAudienceMembers($groups, $ordered = true) {
        global $db;

        $query_ordered = "";
        if ($ordered) {
            $query_ordered = "ORDER BY a.`gender` DESC";
        }


        $query	= "	SELECT a.`id`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    JOIN `group_members` c
                    ON a.`id` = c.`proxy_id`
                    AND c.`group_id` IN(?)
                    AND c.`member_active` = '1'
                    WHERE b.`app_id` IN (?)					
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND (c.`start_date` = '0' OR c.`start_date` <= ?)
                    AND (c.`finish_date` = '0' OR c.`finish_date` > ?)											
                    GROUP BY a.`id` ".$query_ordered;

        $results = $db->GetAll($query, array($groups, AUTH_APP_IDS_STRING, time(), time(), time(), time()));

        if ($results) {
            return $results;
        }
        return false;

    }

    public function getExportGroupAudienceByGroupID($group_id) {
        global $db;

        $query = "SELECT a.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname` FROM `course_group_audience` AS a
						JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`cgroup_id` = ?
						AND a.`active` = 1";
        $course_group_audience = $db->GetAll($query, array($group_id));

        if ($course_group_audience) {
            return $course_group_audience;
        }
        return false;

    }

}