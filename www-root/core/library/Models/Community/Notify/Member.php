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
 * A model for handling course contacts.
 *
 * @author Organisation: Queen's University
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */


class Models_Community_Notify_Member extends Models_Base {
    protected   $cnmember_id,
                $proxy_id,
                $record_id,
                $community_id,
                $notify_type,
                $notify_active;

    protected static $table_name          = "community_notify_members";
    protected static $primary_key         = "cnmember_id";
    protected static $default_sort_column = "cnmember_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCNMemberID() {
        return $this->cnmember_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getRecordID() {
        return $this->record_id;
    }

    public function getNotifyType() {
        return $this->notify_type;
    }

    public function getNotifyActive() {
        return $this->notify_active;
    }

    public static function fetchRowByProxyIDCommunityIDNotifyType($proxy_id, $community_id = 0, $notify_type = "") {
        $self = new self();
        return $self->fetchRow(
            array(
                "proxy_id" => $proxy_id,
                "community_id" => $community_id,
                "notify_type" => $notify_type
            )
        );
    }

    public static function fetchAllByCommunityID($community_id) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "community_id",
                "value"     => $community_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function getAllCommunityNotificationsByProxyID($proxy_id, $search_term = "", $offset = null, $limit = null, $sort_column = null, $sort_direction = null) {
        global $db;

        $sort_columns_array = array(
            "title" => "e.`community_title`"
        );

        $order_sql = " ORDER BY ".$sort_columns_array[$sort_column]. " ".$sort_direction." " ;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND  e.`community_title` LIKE (". $db->qstr($search_term) . ")";
        }


        $query = "SELECT DISTINCT(a.`community_id`), a.`member_acl`, e.`community_title`, b.`notify_active` AS `announcement`, c.`notify_active` AS `event`, d.`notify_active` AS `poll`, f.`notify_active` AS `members`
                    FROM `community_members` AS a
                    LEFT JOIN `community_notify_members` AS b
                    ON a.`community_id` = b.`community_id`
                    AND a.`proxy_id` = b.`proxy_id`
                    AND b.`notify_type` = 'announcement'
                    LEFT JOIN `community_notify_members` AS c
                    ON a.`community_id` = c.`community_id`
                    AND a.`proxy_id` = c.`proxy_id`
                    AND c.`notify_type` = 'event'
                    LEFT JOIN `community_notify_members` AS d
                    ON a.`community_id` = d.`community_id`
                    AND a.`proxy_id` = d.`proxy_id`
                    AND d.`notify_type` = 'poll'
                    LEFT JOIN `communities` AS e
                    ON a.`community_id` = e.`community_id`
                    LEFT JOIN `community_notify_members` AS f
                    ON a.`community_id` = f.`community_id`
                    AND a.`proxy_id` = f.`proxy_id`
                    AND f.`notify_type` = 'members'
                    WHERE a.`proxy_id` = ? 
                    AND a.`member_active` = 1 
                    " . $search_sql . "
                    " . $order_sql . "
                    LIMIT ? , ? ";

        $results = $db->GetAll($query, array($proxy_id, $offset, $limit));

        if ($results) {
            return $results;
        }

        return false;
    }

    public static function getTotalCommunityNotificationsByProxyID($proxy_id, $search_term = "") {
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND  e.`community_title` LIKE (". $db->qstr($search_term) . ")";
        }

        $query = "SELECT COUNT(DISTINCT(a.`community_id`)) as total_rows
                    FROM `community_members` AS a
                    LEFT JOIN `community_notify_members` AS b
                    ON a.`community_id` = b.`community_id`
                    AND a.`proxy_id` = b.`proxy_id`
                    AND b.`notify_type` = 'announcement'
                    LEFT JOIN `community_notify_members` AS c
                    ON a.`community_id` = c.`community_id`
                    AND a.`proxy_id` = c.`proxy_id`
                    AND c.`notify_type` = 'event'
                    LEFT JOIN `community_notify_members` AS d
                    ON a.`community_id` = d.`community_id`
                    AND a.`proxy_id` = d.`proxy_id`
                    AND d.`notify_type` = 'poll'
                    LEFT JOIN `communities` AS e
                    ON a.`community_id` = e.`community_id`
                    LEFT JOIN `community_notify_members` AS f
                    ON a.`community_id` = f.`community_id`
                    AND a.`proxy_id` = f.`proxy_id`
                    AND f.`notify_type` = 'members'
                    WHERE a.`proxy_id` = ? 
                    AND a.`member_active` = 1 
                    " . $search_sql;

        $results = $db->getRow($query, array($proxy_id));

        if ($results) {
            return $results;
        }

        return false;
    }
}