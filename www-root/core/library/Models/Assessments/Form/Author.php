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

class Models_Assessments_Form_Author extends Models_Base {
    protected $afauthor_id, $form_id, $author_type, $author_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_form_authors";
    protected static $primary_key = "afauthor_id";
    protected static $default_sort_column = "afauthor_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afauthor_id;
    }

    public function getAfauthorID() {
        return $this->afauthor_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getAuthorType() {
        return $this->author_type;
    }

    public function getAuthorID() {
        return $this->author_id;
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

    public function getAuthorName() {
        global $db; 
        
        $return = false;
        switch ($this->author_type) {
            case "proxy_id" :
                $user = Models_User::fetchRowByID($this->author_id);
                if ($user) {
                    $return = $user->getFirstname() . " " . $user->getLastname();
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

    public static function fetchRowByID($afauthor_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afauthor_id", "value" => $afauthor_id, "method" => "=")
        ));
    }
    
    public static function fetchRowByFormIDAuthorIDAuthorType($form_id, $author_id, $author_type) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "=")
        ));
    }

    public static function fetchAllByFormIDAuthorIDAuthorType($author_id, $author_type) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "=")
        ));
    }

    public static function fetchAllRecords($form_id = NULL) {
        $constraints = array(
            array("key" => "afauthor_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        );
        
        if (!is_null($form_id)) {
            $constraints[] = array("key" => "form_id", "value" => (int) $form_id, "method" => "=");
        }
        
        $self = new self();
        return $self->fetchAll($constraints);
    }
    
    public static function fetchAvailableAuthors($filter_type, $form_id, $search_value) {
        global $db, $ENTRADA_USER;
        
        switch ($filter_type) {
            case "organisation_id" :
                if (empty($search_value)) {
                    $query = "SELECT a.`organisation_id` AS `id`, `organisation_title` AS `fullname`, '' AS `email`
                                            FROM `".AUTH_DATABASE."`.`organisations` AS a
                                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                            ON a.`organisation_id` = b.`organisation_id`
                                            LEFT JOIN `cbl_assessment_form_authors` AS c
                                            ON a.`organisation_id` = c.`author_id`
                                            AND c.`author_type` = 'organisation_id'
                                            AND c.`form_id` = ".$db->qstr($form_id)."
                                            AND c.`deleted_date` IS NULL
                                            WHERE b.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveID())."
                                            AND c.`form_id` IS NULL
                                            GROUP BY a.`organisation_id`";
                } else {
                    $query = "SELECT a.`organisation_id` AS `id`, a.`organisation_title` AS `fullname`, '' AS `email`
                                            FROM `" . AUTH_DATABASE . "`.`organisations` AS a
                                            LEFT JOIN `cbl_assessment_form_authors` AS b
                                            ON a.`organisation_id` = b.`author_id`
                                            AND b.`author_type` = 'organisation_id'
                                            AND b.`form_id` = ".$db->qstr($form_id)."
                                            AND b.`deleted_date` IS NULL
                                            WHERE a.`organisation_title` LIKE (" . $db->qstr($search_value) . ")
                                            AND b.`form_id` IS NULL";
                }
                break;
            case "course_id" :
                $query = "SELECT a.`course_id` AS `id`, CONCAT(a.`course_code`, ' - ', a.`course_name`) AS `fullname`, '' AS `email`
                                        FROM `courses` AS a
                                        LEFT JOIN `cbl_assessment_form_authors` AS b
                                        ON a.`course_id` = b.`author_id`
                                        AND b.`author_type` = 'course_id'
                                        AND b.`form_id` = ".$db->qstr($form_id)."
                                        AND b.`deleted_date` IS NULL
                                        WHERE (a.`course_code` LIKE (".$db->qstr($search_value).")
                                        OR a.`course_name` LIKE (".$db->qstr($search_value)."))
                                        AND a.`course_active` = '1'
                                        AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                                        AND b.`form_id` IS NULL
                                        ORDER BY a.`course_code`";
                break;
            case "proxy_id" :
                $query = "SELECT a.`id`, CONCAT(a.`firstname`, ' ', a.`lastname`) AS `fullname`, a.`email`
                                        FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                                        LEFT JOIN `cbl_assessment_form_authors` AS b
                                        ON a.`id` = b.`author_id`
                                        AND b.`author_type` = 'proxy_id'
                                        AND b.`form_id` = ".$db->qstr($form_id)."
                                        AND b.`deleted_date` IS NULL
                                        WHERE b.`form_id` IS NULL
                                        HAVING `fullname` LIKE (".$db->qstr($search_value).")
                                        OR `email` LIKE (".$db->qstr($search_value).")";
                break;
        }

        return $db->GetAll($query);
    }
    
    public static function fetchAllByFormID ($form_id = null, $organisation_id = null) {
        global $db;
        
        $query = "  SELECT a.*, b.* FROM `cbl_assessments_lu_forms` AS a
                    JOIN `cbl_assessment_form_authors` AS b
                    ON a.`form_id` = b.`form_id`
                    WHERE a.`form_id` = ?
                    AND a.`organisation_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL";
        
        $authors = false;
        $results = $db->GetAll($query, array($form_id, $organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array("afauthor_id" => $result["afauthor_id"], "form_id" => $result["form_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
            }
        }
        
        return $authors;
    }
    
    public static function fetchByAuthorTypeProxyID ($organisation_id = null, $search_value = null) {
        global $db;
        $authors = false;
        
        $query = "  SELECT a.`form_id`, b.*, c.`id`, c.`firstname`, c.`lastname` FROM `cbl_assessments_lu_forms` AS a
                    JOIN `cbl_assessment_form_authors` AS b
                    ON a.`form_id` = b.`form_id`
                    JOIN `". AUTH_DATABASE ."`".".`user_data` AS c
                    ON b.`author_id` = c.`id`
                    WHERE a.`organisation_id` = ?
                    AND (c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .") OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") ."))
                    AND b.`author_type` = 'proxy_id'
                    GROUP BY b.`author_id`";
        
        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array("afauthor_id" => $result["afauthor_id"], "form_id" => $result["form_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
            }
        }
        
        return $authors;
    }
}