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
 * A model for handling Course Groups
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */

class Models_Group extends Models_Base  {
    protected   $group_id,
                $group_name,
                $group_type,
                $group_value,
                $start_date,
                $expire_date,
                $group_active = 1,
                $created_date,
                $created_by,
                $updated_date,
                $updated_by;
    
    protected static $primary_key = "group_id";
    protected static $table_name = "groups";
    protected static $default_sort_column = "group_name";
    protected static $database_name = DATABASE_NAME;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->group_id;
    }
    
    public function getGroupName() {
        return $this->group_name;
    }
    
    public function getGroupType() {
        return $this->group_type;
    }
    
    public function getGroupValue() {
        return $this->group_value;
    }
    
    public function getStartDate() {
        return $this->start_date;
    }
    
    public function getExpireDate() {
        return $this->expire_date;
    }
    
    public function getGroupActive() {
        return $this->group_active;
    }
    
    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    /* @return bool|Models_Group */
    public static function fetchRowByID($group_id) {
        $self = new self();

        return $self->fetchRow(array(
            array("key" => "group_id", "value" => $group_id, "method" => "=")
        ));
    }

    public static function fetchRowByName($group_name, $organisation_id, $active = 1) {
        global $db;

        $organisation_id = (int) $organisation_id;
        $active = (($active == 1) ? 1 : 0);

		$query = "SELECT a.`group_id`
                    FROM `groups` AS a
                    JOIN `group_organisations` AS b
                    ON a.`group_id` = b.`group_id`
                    WHERE a.`group_name` like ?
                    AND b.`organisation_id` = ?
                    AND a.`group_active` = ?";

		return $db->GetOne($query, array($group_name, (int)$organisation_id, $active));
    }

    public static function getGroupMembersByGroupID($group_id, $search_term = "", $offset = null, $limit = null, $sort_column = null, $sort_direction = null) { // Not used
        global $db;

        $sort_columns_array = array(
            "name" => "`a`.`lastname`",
        );

        $order_sql = " ORDER BY ".$sort_columns_array[$sort_column]. " ".$sort_direction." " ;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND  `a`.`lastname` LIKE (". $db->qstr($search_term) . ") OR `a`.`firstname` LIKE (". $db->qstr($search_term) . ")";
        }

        $query	= "	SELECT c.`cgroup_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, c.`active`,
                    a.`username`, a.`organisation_id`, a.`username`, CONCAT_WS(':', b.`group`, b.`role`) AS `grouprole`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    INNER JOIN `group_members` AS c 
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

    public function getTotalGroupMembers($group_id = null, $active = 1) {
        global $db;

        if ($group_id == null) {
        	$group_id = $this->group_id;
		}

        $query	= "	SELECT COUNT(*) as total_row
                    FROM `group_members` AS g
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS a
                    ON g.`proxy_id` = a.`user_id`
                    WHERE `group_id` = ? " .
                    ($active ? "AND g.`member_active` = '1' AND a.`account_active` = 'true'":"");

        $results = $db->GetRow($query, array($group_id));
        if ($results) {
            return $results;
        }

        return false;
    }

    public static function doAction($group_id, $action) {
		global $db, $ENTRADA_USER;;

		$name = $db->GetOne("	SELECT `group_name`
								FROM `".static::$database_name."`.`".static::$table_name."`
								WHERE `group_id`=".$db->qstr($group_id));
		switch ($action) {
			case "deactivate":
				$db->Execute("UPDATE `".static::$database_name."`.`".static::$table_name."` SET `group_active`='0', `updated_date` = '". time() . "', `updated_by` = '".$ENTRADA_USER->getActiveID()."' WHERE `group_id` = ".$db->qstr($group_id));
				break;
			case "activate":
				$db->Execute("UPDATE `".static::$database_name."`.`".static::$table_name."` SET `group_active`='1', `updated_date` = '". time() . "', `updated_by` = '".$ENTRADA_USER->getActiveID()."' WHERE `group_id` = ".$db->qstr($group_id));
				break;
			case "delete":
				$db->Execute("DELETE FROM `group_members` WHERE `group_id` = ".$db->qstr($group_id));
				$db->Execute("DELETE FROM `".static::$database_name."`.`".static::$table_name."` WHERE `group_id` = ".$db->qstr($group_id));
				$db->Execute("DELETE FROM `group_organisations` WHERE `group_id` = ".$db->qstr($group_id));
				break;
		}
		return $name;
	}

    public static function fetchAllByOrganisation($search_value = "", $organisation_id) {
       global $db;

        $output = array();
        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `group_organisations` AS b
                    ON a.`group_id` = b.`group_id`
                    WHERE b.`organisation_id` = ".$db->qstr($organisation_id)."
                    AND a.`group_name` LIKE ".$db->qstr("%".$search_value."%");
        $results = $db->GetAll($query);
        foreach ($results as $result) {
            $output[] = new self($result);
        }

        return $output;
    }

    public static function fetchAllByOrganisationID($organisation_id) {
        global $db;

        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `group_organisations` AS b
                    ON a.`group_id` = b.`group_id`
                    WHERE b.`organisation_id` = ? ";

        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            return $results;
        }

        return false;
    }

    public static function fetchAllActiveByOrganisationID($organisation_id) {
        global $db;

        $query = "SELECT a.* FROM `" . static::$database_name . "`.`" . static::$table_name . "` AS a
                    JOIN `group_organisations` AS b
                    ON a.`group_id` = b.`group_id`
                    WHERE b.`organisation_id` = ?
                    AND a.`group_active` = 1";

        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            return $results;
        }

        return false;
    }

    public static function fetchAllByGroupType($group_type, $organisation_id, $search_term) {
        global $db;

        $output = array();

        $query = "  SELECT a.*
                    FROM `groups` AS a
                    JOIN `group_organisations` AS b
                    ON a.`group_id` = b.`group_id`
                    WHERE a.`group_type` = " . $db->qstr($group_type) . "
                    AND b.`organisation_id` = " . $db->qstr($organisation_id) . "
                    AND a.`group_name` LIKE " . $db->qstr("%" . $search_term . "%");

        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    public static function fetchAllByGroupTypeCourseID($group_type, $organisation_id, $course_id = 0) {
        global $db;

        $output = array();

        $course_sql = "";
        if ($course_id) {
            $course_sql = " AND a.`group_value` = " . $db->qstr($course_id);
        }


        $query = "  SELECT a.*
                    FROM `groups` AS a
                    JOIN `group_organisations` AS b
                    ON a.`group_id` = b.`group_id`
                    WHERE a.`group_type` = " . $db->qstr($group_type) . "
                    AND b.`organisation_id` = " . $db->qstr($organisation_id) . "
                    AND a.`group_active` = 1
                    ". $course_sql. "
                    ORDER BY a.`group_name` DESC";

        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

	public static function getCountAllGroups($organisation_id, $active = 1, $search = false) {
		global $db;

		$query = "  SELECT COUNT(*)
                    FROM `groups` AS a
                    JOIN `group_organisations` AS b
                    ON a.`group_id` = b.`group_id`
                    WHERE b.`organisation_id` = " . $db->qstr($organisation_id) .
			($active?" AND a.`group_active` = '1'":"") .
			($search?" AND (a.`group_name` LIKE ".$db->qstr("%%".str_replace("%", "", $search)."%%").")  ":"");

		return $db->GetOne($query);
	}

	public static function fetchGroupsInList($group_list) {
		global $db;

		$output = array();

		$query = "  SELECT * FROM `" . static::$database_name . "`.`" . static::$table_name . "`
                    WHERE `group_id` IN (".implode(", ", $group_list).")
                    ORDER BY `group_name` ASC";
		$results = $db->GetAll($query);

		if ($results) {
			foreach ($results as $result) {
				$output[] = new self($result);
			}
			return $output;
		} else {
			application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());
			return false;
		}
	}

	public static function getAllGroups($organisation_id, $active, $search, $sort, $limit_parameter, $limit) {
		global $db;

		$query = "	SELECT a.*, COUNT(DISTINCT d.`user_id`) AS members, CASE WHEN (MIN(b.`member_active`) = 0) THEN 1 ELSE 0 END AS `inactive`,
					IF (a.`expire_date`>0,if (UNIX_TIMESTAMP()>a.`expire_date`,1,0),0) AS `expired`	
					FROM `groups` AS a
					LEFT JOIN `group_members` b
					ON b.`group_id` = a.`group_id`
					JOIN `group_organisations` AS c
					ON c.`group_id` = a.`group_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS d
                    ON b.`proxy_id` = d.`user_id`
					WHERE c.`organisation_id` = ".$db->qstr($organisation_id) .
			($active?" AND a.`group_active` = '1'":"") .
			($search?" AND (a.`group_name` LIKE ".$db->qstr("%%".str_replace("%", "", html_encode($search))."%%").")  ":"") . "
					GROUP By a.`group_id` ORDER BY $sort LIMIT $limit_parameter,  $limit";

		return $db->GetAll($query);
	}

	public static function getName($id) {
		global $db;

		return $db->GetOne("SELECT TRIM(`group_name`) FROM `" . static::$database_name . "`.`" . static::$table_name . "` WHERE `group_id` =	".$db->qstr($id));
	}

	public static function updateName($id, $name) {
		global $db, $ENTRADA_USER;

		if (Models_Group::fetchRowByName($name,$ENTRADA_USER->getActiveOrganisation())) {
			application_log("error", "Unable to rename group[$id] a group with that name already exists");
			return false;
		}

		return $db->Execute("UPDATE `" . static::$database_name . "`.`" . static::$table_name . "` SET `group_name`='$name', `updated_date` = '". time() . "', `updated_by` = '".$ENTRADA_USER->getActiveID()."' WHERE `group_id` = ".$db->qstr($id));
	}

	public static function fetchProxyByFullname($last, $first) {
		global $db;

		return $db->GetOne("	SELECT `id` FROM `" . AUTH_DATABASE . "`.`user_data`
								WHERE `firstname` LIKE " . $db->qstr($first) . " AND `lastname` LIKE " . $db->qstr($last));
	}

	public function addMembers($members, $entrada_only = 1) {
		global $db;

		$entry = array("group_id" => $this->group_id, "entrada_only" => $entrada_only, "created_date" => $this->updated_date, "created_by" => $this->updated_by, "updated_date" => $this->updated_date, "updated_by" => $this->updated_by);
		$count = 0;

		foreach ($members as $proxy_id) {
			$entry["proxy_id"] = $proxy_id;

			if(($return=Models_Group_Member::addMember($entry))===false) {
				return false;
			} elseif (!$return) {
				application_log("notice", "Warning user [$proxy_id] is already a member of group [". $this->group_name ."].");
			} else {
				$count++;
			}
		}
		return $count;
	}

	public function membership() {
		global $db;

		return $db->GetRow("SELECT COUNT(DISTINCT a.`gmember_id`) AS members, case when (MIN(a.`member_active`)=0) then 1 else 0 end as `inactive` FROM  `group_members` as a 
                                LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                ON a.`proxy_id` = b.`user_id`
                                WHERE b.`account_active` = 'true'
					            AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
					            AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
                                AND `group_id` = " . $db->qstr($this->group_id));
	}

	public function members($order_by = "default") {
		global $db;
		
		if (!strcmp($order_by,"default")) {
			$order_by = "ORDER BY `lastname`, `firstname`";
		}

		$query	= "	SELECT c.`proxy_id`, c.`gmember_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, c.`member_active`,
				  	a.`username`, a.`number`, a.`lastname`, a.`firstname`, a.`organisation_id`, b.`group`, CONCAT_WS(':', b.`group`, b.`role`) AS `grouprole`
				  	FROM `".AUTH_DATABASE."`.`user_data` AS a
				  	LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
		  			ON a.`id` = b.`user_id`
					INNER JOIN `group_members` c ON a.`id` = c.`proxy_id`
		  			WHERE b.`account_active` = 'true'
					AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
					AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
					AND c.`group_id` = ".$db->qstr($this->group_id)."
		  			GROUP BY a.`id`
  					$order_by";

		return $db->GetAll($query);
	}
}
