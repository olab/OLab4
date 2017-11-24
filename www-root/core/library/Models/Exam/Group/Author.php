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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 */

class Models_Exam_Group_Author extends Models_Base {
    protected $egauthor_id, $group_id, $author_type, $author_id, $group, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "exam_group_authors";
    protected static $primary_key = "egauthor_id";
    protected static $default_sort_column = "egauthor_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->egauthor_id;
    }

    public function getEgauthorID() {
        return $this->egauthor_id;
    }

    public function getGroupID() {
        return $this->group_id;
    }

    public function getAuthorType() {
        return $this->author_type;
    }

    public function getAuthorID() {
        return $this->author_id;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }
    
    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setGroupId($group_id) {
        $this->group_id = $group_id;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }
    
    public function getAuthorName() {
        global $db; 
        
        $return = false;
        switch ($this->author_type) {
            case "proxy_id" :
                $user = User::fetchRowByID($this->author_id, true, null, 1);
                if ($user) {
                    $return = $user->getFullname(false);
                }
            break;
            case "organisation_id" :
                $query = "SELECT * FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ?";
                $result = $db->GetRow($query, array($this->author_id));
                if ($result) {
                    $return = $result["organisation_title"];
                }
            break;
            case "course_id" :
                $course = Models_Course::get($this->author_id);
                if ($course) {
                    $return = $course->getCourseCode() . " - " . $course->getCourseName();
                }
            break;
            default :
                $return = false;
            break;
        }
        return $return;
    }

    /* @return bool|Models_Exam_Group */
    public function getGroup(){
        if (NULL === $this->group) {
            $this->group = Models_Exam_Group::fetchRowByID($this->getGroupID());
        }
        return $this->group;
    }

    /* @return bool|Models_Exam_Group */
    public static function fetchRowByID($egauthor_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "egauthor_id", "value" => $egauthor_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Exam_Group */
    public static function fetchRowByGroupIDAuthorIDAuthorType($group_id, $author_id, $author_type = "proxy_id") {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Exam_Group[] */
    public static function fetchAllByAuthorIDAuthorType($author_id, $author_type = "proxy_id", $search_value = null, $deleted_date = null) {
        if (!is_null($search_value)){
            global $db;
            $query = "SELECT a.*, b.* FROM `exam_group_authors` AS a
                        JOIN `exam_groups` AS b
                        ON a.`group_id` = b.`group_id`
                        WHERE a.`author_type` = ?
                        AND a.`author_id` = ?
                        AND b.`group_title` LIKE (" . $db->qstr("%".$search_value."%") . ")";
                        //AND a.`deleted_date` " . ($deleted_date ? "<= " . $deleted_date : "IS NULL");
            $authors = false;
            $results = $db->GetAll($query, array($author_type, $author_id));
            if ($results) {
                foreach ($results as $result) {
                    $authors[] = new self(array("egauthor_id" => $result["egauthor_id"], "group_id" => $result["group_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
                }
            }

            return $authors;

        } else {
            $constraints = array(
                array("key" => "author_id", "value" => $author_id, "method" => "="),
                array("key" => "author_type", "value" => $author_type, "method" => "="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            );

            $self = new self();
            return $self->fetchAll($constraints);
        }
    }

    /* @return ArrayObject|Models_Exam_Group[] */
    public static function fetchAllRecords($group_id = NULL) {
        $constraints = array(
            array("key" => "egauthor_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        );
        
        if (!is_null($group_id)) {
            $constraints[] = array("key" => "group_id", "value" => (int) $group_id, "method" => "=");
        }
        
        $self = new self();
        return $self->fetchAll($constraints);
    }
    
    public static function fetchAvailableAuthors($filter_type, $group_id, $search_value) {
        global $db, $ENTRADA_USER;
        
        switch ($filter_type) {
            case "organisation_id" :
                if (empty($search_value)) {
                    $query = "SELECT a.`organisation_id` AS `id`, `organisation_title` AS `fullname`, '' AS `email`
                                            FROM `".AUTH_DATABASE."`.`organisations` AS a
                                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                            ON a.`organisation_id` = b.`organisation_id`
                                            LEFT JOIN `exam_group_authors` AS c
                                            ON a.`organisation_id` = c.`author_id`
                                            AND c.`author_type` = 'organisation_id'
                                            AND c.`group_id` = ".$db->qstr($group_id)."
                                            AND c.`deleted_date` IS NULL
                                            WHERE b.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveID())."
                                            AND c.`group_id` IS NULL
                                            GROUP BY a.`organisation_id`";
                } else {
                    $query = "SELECT a.`organisation_id` AS `id`, a.`organisation_title` AS `fullname`, '' AS `email`
                                            FROM `" . AUTH_DATABASE . "`.`organisations` AS a
                                            LEFT JOIN `exam_group_authors` AS b
                                            ON a.`organisation_id` = b.`author_id`
                                            AND b.`author_type` = 'organisation_id'
                                            AND b.`group_id` = ".$db->qstr($group_id)."
                                            AND b.`deleted_date` IS NULL
                                            WHERE a.`organisation_title` LIKE (" . $db->qstr($search_value) . ")
                                            AND b.`group_id` IS NULL";
                }
                break;
            case "course_id" :
                $query = "SELECT a.`course_id` AS `id`, CONCAT(a.`course_code`, ' - ', a.`course_name`) AS `fullname`, '' AS `email`
                                        FROM `courses` AS a
                                        LEFT JOIN `exam_group_authors` AS b
                                        ON a.`course_id` = b.`author_id`
                                        AND b.`author_type` = 'course_id'
                                        AND b.`group_id` = ".$db->qstr($group_id)."
                                        AND b.`deleted_date` IS NULL
                                        WHERE (a.`course_code` LIKE (".$db->qstr($search_value).")
                                        OR a.`course_name` LIKE (".$db->qstr($search_value)."))
                                        AND a.`course_active` = '1'
                                        AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                                        AND b.`group_id` IS NULL
                                        ORDER BY a.`course_code`";
                break;
            case "proxy_id" :
                $query = "SELECT a.`id`, CONCAT(a.`firstname`, ' ', a.`lastname`) AS `fullname`, a.`email`
                                        FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                                        LEFT JOIN `exam_group_authors` AS b
                                        ON a.`id` = b.`author_id`
                                        AND b.`author_type` = 'proxy_id'
                                        AND b.`group_id` = ".$db->qstr($group_id)."
                                        AND b.`deleted_date` IS NULL
                                        WHERE b.`group_id` IS NULL
                                        HAVING `fullname` LIKE (".$db->qstr($search_value).")
                                        OR `email` LIKE (".$db->qstr($search_value).")";
                break;
        }

        return $db->GetAll($query);
    }

    /* @return ArrayObject|Models_Exam_Group[] */
    public static function fetchAllByGroupID($group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}