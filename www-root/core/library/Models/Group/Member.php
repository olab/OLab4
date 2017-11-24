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
 * A model for handeling Group Members
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */
class Models_Group_Member extends Models_Base {
    protected $gmember_id,
            $group_id,
            $proxy_id,
            $start_date,
            $finish_date,
            $member_active,
            $entrada_only,
            $created_date,
            $created_by,
            $updated_date,
            $updated_by;

    protected static $table_name = "group_members";
    protected static $default_sort_column = "gmember_id";
    protected static $primary_key = "gmember_id";

    public function getID () {
        return $this->gmember_id;
    }
    
    public function getGroupID () {
        return $this->group_id;
    }
    
    public function getProxyID () {
        return $this->proxy_id;
    }
    
    public function getStartDate () {
        return $this->start_date;
    }
    
    public function getFinishDate () {
        return $this->finish_date;
    }
    
    public function getMemberActive () {
        return $this->member_active;
    }
    
    public function getEntradaOnly () {
        return $this->entrada_only;
    }

    public function getCreatedDate () {
        return $this->created_date;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function getUsersByGroupID($group_id, $search_term = false, $active = 1) {
        global $db;
        $members = false;

        $query	= "	SELECT a.`id`, a.`number`, a.`firstname`, a.`lastname`, c.`gmember_id`, c.`member_active`,
                    a.`username`, a.`email`, a.`organisation_id`, a.`username`, b.`group`, b.`role`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    INNER JOIN `group_members` c ON a.`id` = c.`proxy_id`
                    WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
                    AND c.`group_id` = ?
                    AND c.`member_active` = ?
                    ". (trim($search_term) ? " AND ((CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE ".$db->qstr("%".$search_term."%"). ") OR (a.`number` LIKE " .$db->qstr("%".$search_term."%").") OR (a.`email` LIKE " .$db->qstr("%".$search_term."%")."))" : "") ."
                    GROUP BY a.`id`
                    ORDER BY a.`lastname` ASC, a.`firstname` ASC";
        
        $results = $db->GetAll($query, array($group_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $member = new User();
                $members[] = $member::fromArray($result, $member);
            }
        }
        return $members;
    }

    public static function getUsersByGroupIDWithoutAppID($group_id, $search_term = false, $active = 1, $excluded_ids = 0) {
        global $db;
        $members = false;

        $query	= "	SELECT a.`id`, a.`number`, a.`firstname`, a.`lastname`, c.`gmember_id`, c.`member_active`,
                           a.`username`, a.`email`, a.`organisation_id`, a.`username`, b.`group`, b.`role`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    INNER JOIN `group_members` AS c
                    ON a.`id` = c.`proxy_id`
                    WHERE a.`id` NOT IN (".$excluded_ids.")
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
                    AND c.`group_id` = ?
                    AND c.`member_active` = ?
                    ". (trim($search_term) ? " AND ((CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE ".$db->qstr("%".$search_term."%"). ") OR (a.`number` LIKE " .$db->qstr("%".$search_term."%").") OR (a.`email` LIKE " .$db->qstr("%".$search_term."%")."))" : "") ."
                    GROUP BY a.`id`
                    ORDER BY a.`firstname` ASC, a.`lastname` ASC";

        $results = $db->GetAll($query, array($group_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $member = new User();
                $members[] = $member::fromArray($result, $member);
            }
        }
        return $members;
    }

    public static function getUser($proxy_id = null, $search_term = false) {
        global $db;
        $member = false;
        
        $query = "	SELECT a.`id`, a.`number`, a.`firstname`, a.`lastname`,
                    a.`username`, a.`email`, a.`organisation_id`, b.`group`, b.`role`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND a.`id` = ?
                    ". (trim($search_term) ? " AND ((CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE ".$db->qstr("%".$search_term."%"). ") OR (a.`number` LIKE " .$db->qstr("%".$search_term."%").") OR (a.`email` LIKE " .$db->qstr("%".$search_term."%")."))" : "") ."
                    GROUP BY a.`id`
                    ORDER BY a.`lastname` ASC, a.`firstname` ASC";
        
        $result = $db->GetRow($query, array(time(), time(), $proxy_id));
        if ($result) {
            $m = new User();
            $member = User::fromArray($result, $m);
        }
        return $member;
    }

    public static function getAssessmentGroupMembers ($organisation_id = null, $group_id = null, $active = 1) {
        global $db;

        $query	= "	SELECT a.`id`, a.`number`, a.`firstname`, a.`lastname`, a.`email`, c.`gmember_id`, c.`member_active`,
                    a.`username`, a.`email`, a.`organisation_id`, a.`username`, b.`group`, b.`role`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    JOIN `group_members` c ON a.`id` = c.`proxy_id`
                    WHERE b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
                    AND b.`organisation_id` = ?
                    AND c.`group_id` = ?
                    AND c.`member_active` = ?
                    GROUP BY a.`id`
                    ORDER BY a.`lastname` ASC, a.`firstname` ASC";

        $group_members = array();

        $results = $db->GetAll($query, array($organisation_id, $group_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $group_members[] = array(
                    "name" => $result["firstname"] . " " . $result["lastname"],
                    "firstname" => $result["firstname"],
                    "lastname" => $result["lastname"],
                    "proxy_id" => $result["id"],
                    "email" => $result["email"],
                    "number" => $result["number"]);
            }
        }

        return $group_members;
    }
}
