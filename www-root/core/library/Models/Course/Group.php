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
 * A class for handling course groups
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 David Geffen School of Medicine at UCLA
 */

class Models_Course_Group extends Models_Base {
    protected $cgroup_id, $course_id, $cperiod_id, $group_name, $group_type, $active;

    protected static $table_name = "course_groups";
    protected static $primary_key = "cgroup_id";
    protected static $default_sort_column = "group_name";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cgroup_id;
    }

    public function getCgroupID() {
        return $this->cgroup_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCPeriodID() {
        return $this->cperiod_id;
    }

    public function getGroupName() {
        return $this->group_name;
    }

    public function getActive() {
        return $this->active;
    }

    public function getGroupType() {
        return $this->group_type;
    }

    public static function fetchAllRecords($active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    public static function fetchAllRecordsByContactProxyID($proxy_id, $organisation_id) {
        global $db;
        $output = array();

        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `".static::$database_name."`.`course_group_contacts` AS b
                    ON a.`cgroup_id` = b.`cgroup_id`
                    JOIN `".static::$database_name."`.`courses` AS c
                    ON a.`course_id` = c.`course_id`
                    WHERE b.`proxy_id` = ?
                    AND c.`organisation_id` = ?
                    AND c.`course_active` = 1
                    AND a.`active` = 1";
        $results = $db->GetAll($query, array($proxy_id, $organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Course_Group($result);
            }
        }

        return $output;
    }

    public static function facultyMemberIsTutor($faculty_proxy_id, $learner_proxy_id, $organisation_id) {
        global $db;

        $output = array();

        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `".static::$database_name."`.`course_group_contacts` AS b
                    ON a.`cgroup_id` = b.`cgroup_id`
                    JOIN `".static::$database_name."`.`course_group_audience` AS c
                    ON a.`cgroup_id` = c.`cgroup_id`
                    JOIN `courses` AS d
                    ON a.`course_id` = d.`course_id`
                    WHERE b.`proxy_id` = ?
                    AND c.`proxy_id` = ?
                    AND d.`organisation_id` = ?
                    AND a.`active` = 1
                    AND c.`active` = 1
                    AND d.`course_active` = 1
                    GROUP BY a.`cgroup_id`";
        $groups = $db->GetAll($query, array($faculty_proxy_id, $learner_proxy_id, $organisation_id));
        if ($groups) {
            foreach ($groups as $group) {
                $output[] = new Models_Course_Group($group);
            }
        }

        return $output;
    }

    /* @return bool|Models_Course_Group */
    public static function fetchRowByID($cgroup_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "cgroup_id", "value" => $cgroup_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    /* @return ArrayObject|Models_Course_Group[] */
    public static function fetchAllByCourseID($course_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "course_id",
                "value"     => $course_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function fetchAllGroupsByTutorProxyIDOrganisationID($proxy_id, $organisation_id) {
        global $db;

        $output = array();

        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `".static::$database_name."`.`course_group_contacts` AS b
                    ON a.`cgroup_id` = b.`cgroup_id`
                    JOIN `courses` AS c
                    ON a.`course_id` = c.`course_id`
                    WHERE b.`proxy_id` = ?
                    AND c.`organisation_id` = ?
                    AND a.`active` = 1
                    AND c.`course_active` = 1
                    GROUP BY a.`cgroup_id`";
        $groups = $db->GetAll($query, array($proxy_id, $organisation_id));
        if ($groups) {
            foreach ($groups as $group) {
                $output[] = new Models_Course_Group($group);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Course_Group[] */
    public static function fetchAllByCgroupID($cgroup_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "cgroup_id",
                "value"     => $cgroup_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Course_Group[] */
    public static function fetchAllByCourseIDCperiodID($course_id = 0, $cperiod_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "course_id",
                "value"     => $course_id,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "cperiod_id",
                "value"     => $cperiod_id,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "active",
                "value"     => 1,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }



    /* @return bool|Models_Course_Group */
    public static function fetchRowByGroupNameCourseID($group_name = 0, $course_id = 0) {
        $self = new self();
        $constraints = array(
            array(
                "key" => "group_name",
                "value" => $group_name
            ),
            array(
                "key" => "course_id",
                "value" => $course_id
            )
        );
        $row = $self->fetchRow($constraints);
        if ($row) {
            return $row;
        }
        return false;
    }

    public static function fetchRowByGroupNameCourseIDCperiodID($group_name = 0, $course_id = 0, $cperiod_id = 0) {
        $self = new self();
        $constraints = array(
            array(
                "key" => "group_name",
                "value" => $group_name
            ),
            array(
                "key" => "course_id",
                "value" => $course_id
            ),
            array(
                "key" => "cperiod_id",
                "value" => $cperiod_id
            )
        );
        $row = $self->fetchRow($constraints);
        if ($row) {
            return $row;
        }
        return false;
    }


    public function getGroupsByCourseID($course_id, $cperiod_id, $search_term = "", $offset = null, $limit = null, $sort_column = null, $sort_direction = null) {
        global $db;

        $sort_columns_array = array(
            "name" => "`a`.`group_name`",
        );

        $order_sql = " ORDER BY ".$sort_columns_array[$sort_column]. " ".$sort_direction." " ;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND  `a`.`group_name` LIKE (". $db->qstr($search_term) . ")";
        }


        $query = "	SELECT a.*, (SELECT COUNT(b.`cgaudience_id`) FROM `course_group_audience` AS b 
	                WHERE a.`cgroup_id` = b.`cgroup_id` AND b.`active` = 1) AS `members`
					FROM `course_groups` AS a
					WHERE a.`course_id` = ? 
					AND (a.`cperiod_id` = ? OR a.`cperiod_id` IS NULL)
					AND a.`active` = 1
					 " . $search_sql . "
					GROUP By a.`cgroup_id`
					 " . $order_sql . "
					LIMIT ?, ? ";

        $results = $db->GetAll($query, array($course_id, $cperiod_id, $offset, $limit));

        if ($results) {
            return $results;
        }
        return false;
    }

    public function getTotalCourseGroups($course_id,  $cperiod_id, $search_term = "") {
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND  `course_groups`.`group_name` LIKE (". $db->qstr($search_term) . ")";
        }

        $query = "	SELECT COUNT(*) AS `total_rows` FROM `course_groups` WHERE `active` = 1 AND `course_id` = ? AND (`cperiod_id` = ? OR `cperiod_id` IS NULL) " . $search_sql."";

        $results = $db->GetRow($query, array($course_id, $cperiod_id));
        if ($results) {
            return $results;
        }
        return false;
    }

    public function deleteByID($id = 0) {
        global $db;

        $query = "DELETE FROM `course_groups` WHERE `cgroup_id`= ?";

        $result = $db->Execute($query, $id);

        if($result) {
            return $db->Affected_Rows();
        }
        return false;
    }

    public function getAllByMultipleGroupID($group_ids) {
        global $db;

        $query = "	SELECT a.*, b.`course_name`, b.`course_code` FROM `course_groups` AS a
                        JOIN `courses` AS b
                        ON a.`course_id` = b.`course_id`
						WHERE a.`cgroup_id` IN (".implode(", ", $group_ids).")
						ORDER BY a.`group_name`";

        $results = $db->GetAll($query);
        if ($results) {
            return $results;
        }
        return false;

    }

}
