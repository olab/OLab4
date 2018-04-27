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
 * 
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Blueprint_Author extends Models_Base {
    protected $afbauthor_id, $form_blueprint_id, $author_type, $author_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_form_blueprint_authors";
    protected static $primary_key = "afbauthor_id";
    protected static $default_sort_column = "afbauthor_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afbauthor_id;
    }

    public function getAfbauthorID() {
        return $this->afbauthor_id;
    }

    public function getFormBlueprintID() {
        return $this->form_blueprint_id;
    }

    public function getAuthorType() {
        return $this->author_type;
    }

    public function getAuthorID() {
        return $this->author_id;
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

    public static function fetchRowByID($afbauthor_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afbauthor_id", "value" => $afbauthor_id, "method" => "=")
        ));
    }

    public static function fetchAvailableAuthors($filter_type, $blueprint_form_id, $search_value) {
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
                                            AND c.`form_id` = ".$db->qstr($blueprint_form_id)."
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
                                            AND b.`form_id` = ".$db->qstr($blueprint_form_id)."
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
                                        AND b.`form_id` = ".$db->qstr($blueprint_form_id)."
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
                                        AND b.`form_id` = ".$db->qstr($blueprint_form_id)."
                                        AND b.`deleted_date` IS NULL
                                        WHERE b.`form_id` IS NULL
                                        HAVING `fullname` LIKE (".$db->qstr($search_value).")
                                        OR `email` LIKE (".$db->qstr($search_value).")";
                break;
        }

        return $db->GetAll($query);
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "afbauthor_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByBlueprintID($blueprint_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "form_blueprint_id", "value" => $blueprint_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public static function fetchRowByFormIDAuthorIDAuthorType($blueprint_id, $author_id, $author_type) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_blueprint_id", "value" => $blueprint_id, "method" => "="),
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "=")
        ));
    }

    /**
     * Fetch all authors by a specific author type and for a certain course.
     * @param null $organisation_id
     * @param null $search_value
     * @param null $courses
     * @return array|bool
     */
    public static function fetchByAuthorTypeProxyIDCourseID ($organisation_id = null, $search_value = null, $courses = null) {
        global $db;
        $authors = false;
        $ids = implode(",", array_map(function ($course) {
            return "'".$course->getID()."'";
        }, $courses));

        $query = "  SELECT b.* FROM `cbl_assessments_lu_form_blueprints` AS a
                    JOIN `cbl_assessments_form_blueprint_authors` AS b
                    ON a.`form_blueprint_id` = b.`form_blueprint_id`
                    JOIN `". AUTH_DATABASE ."`".".`user_data` AS c
                    ON b.`author_id` = c.`id`
                    WHERE a.`organisation_id` = ?
                    AND (c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .") OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") ."))
                    AND b.`author_type` = 'proxy_id'
                    AND a.`course_id` IN (".$ids.")
                    GROUP BY b.`author_id` ";

        $results = $db->GetAll($query, array($organisation_id));

        if ($results) {
            foreach ($results as $result) {
                $author = new self;
                $authors[] = $author->fromArray($result);
            }
        }

        return $authors;
    }

    public static function fetchAllByAuthorTypeAuthorID($author_type, $author_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "=")
        ));
    }
}