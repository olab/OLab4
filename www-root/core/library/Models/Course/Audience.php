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
 * A model for handeling a course audience
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */

class Models_Course_Audience extends Models_Base {
    protected $caudience_id,
              $course_id,
              $cperiod_id,
              $audience_type,
              $audience_value,
              $enroll_start = 0,
              $enroll_finish = 0,
              $ldap_sync_date = 0,
              $audience_active = 1;
    
    protected static $table_name = "course_audience";
    protected static $default_sort_column = "caudience_id";
    protected static $primary_key = "caudience_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID () {
      return $this->caudience_id;
    }
    
    public function getCourseID () {
        return $this->course_id;
    } 
    
    public function getCperiodID () {
        return $this->cperiod_id;
    }
    
    public function getAudienceType () {
        return$this->audience_type;
    }
    
    public function getAudienceValue () {
        return $this->audience_value;
    }
    
    public function getEnrollStart () {
        return $this->enroll_start;
    }
    
    public function getEnrollFinish () {
        return $this->enroll_finish;
    }
    
    public function getAudienceActive () {
        return $this->audience_active;
    }
    
    public function getLdapSyncDate () {
        return $this->ldap_sync_date;
    }
    
    public function setLdapSyncDate ($ldap_sync_date) {
        $this->ldap_sync_date = $ldap_sync_date;
    }
    
    public function fetchAllByCourseIDCperiodID ($course_id = null, $cperiod_id = null, $active = 1) {
        return $this->fetchAll(array("course_id" => $course_id, "cperiod_id" => $cperiod_id, "audience_active" => $active));
    }

    public function fetchAllByCourseID ($course_id = null, $active = 1) {
        return $this->fetchAll(array("course_id" => $course_id, "audience_active" => $active));
    }

    public function fetchRowByCourseIDCperiodID ($course_id = null, $cperiod_id = null, $active = 1) {
        return $this->fetchRow(array("course_id" => $course_id, "cperiod_id" => $cperiod_id, "audience_active" => $active));
    }
    
    public function getMember ($search_term = false) {
        return Models_Group_Member::getUser($this->audience_value, $search_term);
    }

    public function getMembers ($search_term = false) {
        return Models_Group_Member::getUsersByGroupID($this->audience_value, $search_term);
    }
    
    public function getGroupName() {
        $group = Models_Group::fetchRowByID($this->audience_value);
        if ($group) {
            return $group->getGroupName();
        } else {
            return false;
        }
    }
    
    public function fetchRowByCourseIDAudienceTypeAudienceValue ($course_id = null, $audience_type = null, $audience_value = null, $active = 1) {
        return $this->fetchRow(array("course_id" => $course_id, "audience_type" => $audience_type, "audience_value" => $audience_value, "audience_active" => $active));
    }
    
    public function update() {
		global $db;
		if ($db->AutoExecute("`".static::$table_name."`", $this->toArray(), "UPDATE", "`caudience_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
    
    public function getCurriculumPeriod($cperiod_id = null) {
        return Models_Curriculum_Period::fetchRowByID($cperiod_id);
    }

    public function fetchCoursesByCurriculumPeriod($cperiod_ids = array()) {
        global $ENTRADA_USER, $db;

        if ((!is_array($cperiod_ids)) || (!count($cperiod_ids))) {
            return null;
        }
        $current_curriculum_periods = implode(",", $cperiod_ids);

        $query = "SELECT DISTINCT(a.`course_id`), a.`course_twitter_handle` AS `handle`, a.`course_twitter_hashtags` AS `hashtags`
				FROM `courses` AS a
				LEFT JOIN `course_audience` AS e
				ON a.`course_id` = e.`course_id`";

        if (strtolower($ENTRADA_USER->getActiveRole()) != "admin" && strtolower($ENTRADA_USER->getActiveRole()) != "director") {
            $query .= "	LEFT JOIN `course_audience` AS b
				ON a.`course_id` = b.`course_id`
				LEFT JOIN `groups` AS c
				ON b.`audience_type` = 'group_id'
				AND b.`audience_value` = c.`group_id`
				LEFT JOIN `group_members` AS d
				ON d.`group_id` = c.`group_id`";
        }

        $query .= " WHERE `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    AND e.cperiod_id IN(" . $current_curriculum_periods . ")";

        if ($ENTRADA_USER->getActiveGroup() == "student") {
            $query .="	AND (
						d.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
						OR a.`permission` = 'open'
						OR (
							b.`audience_type` = 'proxy_id' AND b.`audience_value` = ".$db->qstr($ENTRADA_USER->getID())."
						)
					)";
        }

        $query .="    AND a.`course_active`='1'";

        return $db->GetAll($query);
    }

    public function deleteByCourseIDPeriodList($course_id = null, $period_list = array()) {
        global $db;

        $query = "	DELETE FROM `course_audience` WHERE `course_id` = ? ".(!empty($period_list)?" AND `cperiod_id` NOT IN (".implode(",",$period_list).")":"");

        $result = $db->Execute($query, array($course_id));

        if ($result) {
            return $result;
        }

        return false;

    }

    public function deleteByCourseIDPeriodID($course_id = null, $period_list = array(), $period_id = 0, $type = "") {
        global $db;

        $additional_query = "";

        $constrains = array($course_id);

        if (!empty($period_list)) {
            $additional_query .=  " AND `audience_value` NOT IN (".implode(",",$period_list).")";
        }

        if ($period_id) {
            $additional_query .=  " AND `cperiod_id` = ? ";
            $constrains[] = $period_id;
        }

        if ($type) {
            $additional_query .=  " AND `audience_type` = ? ";
            $constrains[] = $type;
        }

        $query = "	DELETE FROM `course_audience` WHERE `course_id` = ? ".$additional_query;

        $result = $db->Execute($query, $constrains);

        if ($result) {
            return $result;
        }

        return false;

    }

    public function fetchRowByCourseIDPeriodIDAudienceTypeAudienceValue ($course_id = null, $period_id = null, $audience_type = null, $audience_value = null, $active = 1) {
        return $this->fetchRow(array("course_id" => $course_id, "cperiod_id" => $period_id, "audience_type" => $audience_type, "audience_value" => $audience_value, "audience_active" => $active));
    }

    public function getAllByCourseIDEnrollPeriod($course_id = null, $enroll_time = 0) {
        global $db;

        $query = "SELECT a.* FROM `course_audience` AS a
								JOIN `curriculum_periods` AS b
								ON a.`cperiod_id` = b.`cperiod_id`
								WHERE a.`course_id` = ?
								AND a.`audience_active` = 1
								AND (a.`enroll_finish` = 0 OR a.`enroll_finish` > ? )
								AND b.`finish_date` >= ? ";
        $course_audience = $db->GetAll($query, array($course_id, $enroll_time, $enroll_time));

        if ($course_audience) {
            return $course_audience;
        }

        return false;
    }


    public function getOneByCourseIDCurriculumDate($course_id = null, $date = 0) {
        global $db;

        $query = "SELECT a.* FROM `course_audience` AS a 
								JOIN `curriculum_periods` AS b
								ON a.`cperiod_id` = b.`cperiod_id`
								WHERE a.`course_id` = ? 
								AND a.`audience_active` = 1
								AND b.`start_date` <= ?
								AND b.`finish_date` >= ?";
        $course_audience = $db->GetRow($query, array($course_id, $date, $date));

        if ($course_audience) {
            return $course_audience;
        }

        return false;
    }

    public function getAllByCourseIDOrganisationID($course_id = null, $organisation_id = 0) {
        global $db;

        $query	= "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`lastname`, a.`firstname`, a.`username`, b.`organisation_id`, b.`group`, b.`role`
                            FROM `".AUTH_DATABASE."`.`user_data` AS a
                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                            ON a.`id` = b.`user_id`
                            JOIN `course_audience` AS c
                            ON c.`course_id` = ?
                            AND c.`audience_type` = 'proxy_id'
                            AND a.`id` = c.`audience_value`
                            JOIN `curriculum_periods` AS d
                            ON c.`cperiod_id` = d.`cperiod_id`
                            WHERE b.`app_id` = ?
                            AND b.`account_active` = 'true'
                            AND b.`group` = 'student'
                            AND b.`organisation_id` = ?
                            AND c.`audience_active` = 1
                            AND d.`start_date` <= ?
                            AND d.`finish_date` >= ?
                            
                            UNION
                            
                            SELECT a.`id` AS `proxy_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`lastname`, a.`firstname`, a.`username`, b.`organisation_id`, b.`group`, b.`role`
                            FROM `".AUTH_DATABASE."`.`user_data` AS a
                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                            ON a.`id` = b.`user_id`
                            JOIN `course_audience` AS c
                            ON c.`course_id` = ?
                            AND c.`audience_type` = 'group_id'
                            JOIN `groups` AS d
                            ON c.`audience_value` = d.`group_id`
                            JOIN `group_members` AS e
                            ON d.`group_id` = e.`group_id`
                            AND e.`proxy_id` = a.`id`
                            JOIN `curriculum_periods` AS f
                            ON c.`cperiod_id` = f.`cperiod_id`
                            WHERE b.`app_id` = ?
                            AND b.`account_active` = 'true'
                            AND b.`group` = 'student'
                            AND b.`organisation_id` = ?
                            AND c.`audience_active` = 1
                            AND d.`group_active` = 1
                            AND (d.`start_date` <= ? OR d.`start_date` = 0 OR d.`start_date` IS NULL)
                            AND (d.`expire_date` >= ? OR d.`expire_date` = 0 OR d.`expire_date` IS NULL)
                            
                            GROUP BY a.`id`
                            ORDER BY `lastname` ASC, `firstname` ASC";

        $course_audience = $db->GetAll($query, array($course_id, AUTH_APP_ID, $organisation_id, time(), time(), $course_id, AUTH_APP_ID, $organisation_id, time(), time()));

        if ($course_audience) {
            return $course_audience;
        }
        return false;
    }

    public static function getAllByGroupIDProxyID($group_id = array(), $proxy_id = 0) {
        global $db;

        $query = "SELECT * FROM `course_audience` 
								WHERE (`audience_type` = 'group_id' AND `audience_value` IN ($group_id))
								OR (`audience_type` = 'proxy_id' AND `audience_value` = ?)
								AND `audience_active` = 1
								GROUP BY cperiod_id";

        $course_audience = $db->GetAll($query, array($proxy_id));

        if ($course_audience) {
            return $course_audience;
        }

        return false;
    }

    public function getAllWithGroupNameByCourseID() {
        global $db;
        
        $query = "SELECT * FROM `course_group_audience` a
                    JOIN `course_groups` b
                    ON a.cgroup_id = b.cgroup_id
                    WHERE b.course_id = ?
                    AND b.cperiod_id = ?
                    AND a.active = 1
                    ORDER BY b.group_name ASC";

        $results = $db->getAll($query, array($this->course_id, $this->cperiod_id));

        if ($results) {
            return $results;
        }

        return false;
    }

    /**
     * Get the entire audience for a course + curriculum period as a list of users
     *
     * @param null $course_id
     * @param int  $cperiod_id
     * @param int  $organisation_id
     *
     * @return array|bool
     */
    public function getAllUsersByCourseIDCperiodIDOrganisationID($course_id = null, $cperiod_id = 0, $organisation_id = 0, $search_term = null) {
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND ( `a`.`firstname` LIKE (" . $db->qstr($search_term) . ")
                            OR `a`.`lastname` LIKE (" . $db->qstr($search_term) . ")
                            ) ";
        }

        $query	= "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`lastname`, a.`firstname`, a.`username`, b.`organisation_id`, b.`group`, b.`role`
                            FROM `".AUTH_DATABASE."`.`user_data` AS a
                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                            ON a.`id` = b.`user_id`
                            JOIN `course_audience` AS c
                            ON c.`course_id` = ?
                            AND c.`cperiod_id` = ?
                            AND c.`audience_type` = 'proxy_id'
                            AND a.`id` = c.`audience_value`
                            JOIN `curriculum_periods` AS d
                            ON c.`cperiod_id` = d.`cperiod_id`
                            WHERE b.`app_id` = ?
                            AND b.`account_active` = 'true'
                            AND b.`group` = 'student'
                            AND b.`organisation_id` = ?
                            ".$search_sql."
                            AND c.`audience_active` = 1
                            
                            UNION
                            
                            SELECT a.`id` AS `proxy_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`lastname`, a.`firstname`, a.`username`, b.`organisation_id`, b.`group`, b.`role`
                            FROM `".AUTH_DATABASE."`.`user_data` AS a
                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                            ON a.`id` = b.`user_id`
                            JOIN `course_audience` AS c
                            ON c.`course_id` = ?
                            AND c.`cperiod_id` = ?
                            AND c.`audience_type` = 'group_id'
                            JOIN `groups` AS d
                            ON c.`audience_value` = d.`group_id`
                            JOIN `group_members` AS e
                            ON d.`group_id` = e.`group_id`
                            AND e.`proxy_id` = a.`id`
                            JOIN `curriculum_periods` AS f
                            ON c.`cperiod_id` = f.`cperiod_id`
                            WHERE b.`app_id` = ?
                            AND b.`account_active` = 'true'
                            AND b.`group` = 'student'
                            AND b.`organisation_id` = ?
                            ".$search_sql."
                            AND c.`audience_active` = 1
                            AND d.`group_active` = 1
                            
                            GROUP BY a.`id`
                            ORDER BY `lastname` ASC, `firstname` ASC";

        $course_audience = $db->GetAll($query, array($course_id, $cperiod_id, AUTH_APP_ID, $organisation_id, $course_id, $cperiod_id, AUTH_APP_ID, $organisation_id));

        if ($course_audience) {
            return $course_audience;
        }
        return false;
    }
}

