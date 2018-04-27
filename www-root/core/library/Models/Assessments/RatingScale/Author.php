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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_RatingScale_Author extends Models_Base {
    protected $rating_scale_author_id, $rating_scale_id, $author_type, $author_id, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;

    protected static $table_name = "cbl_assessment_rating_scale_authors";
    protected static $default_sort_column = "rating_scale_id";
    protected static $primary_key = "rating_scale_author_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rating_scale_author_id;
    }

    public function getRatingScaleAuthorID() {
        return $this->rating_scale_author_id;
    }

    public function getRatingScaleID() {
        return $this->rating_scale_id;
    }

    public function getAuthorType() {
        return $this->author_type;
    }

    public function getAuthorID() {
        return $this->author_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function getRowByID($rating_scale_author_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_author_id", "value" => $rating_scale_author_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowsByRatingScaleID($rating_scale_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "rating_scale_id", "value" => $rating_scale_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchFirstRecord($deleted_date = NULL, $sort_column = NULL, $sort_direction = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))), "=", "AND", $sort_column, $sort_direction
        );
    }

    public static function fetchAllRecords($deleted_date = NULL, $sort_column = NULL, $sort_direction = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array(
                "key" => "deleted_date",
                "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")
            )),
            "=",
            "AND",
            $sort_column,
            $sort_direction);
    }

    public static function fetchAllRecordsBySearchTerm($search_value, $limit, $offset, $sort_direction, $sort_column, $filters = array(), $item_id = NULL) {
        /*
                global $db;
                global $ENTRADA_USER;

                if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
                    $sort_column = $tmp_input;
                } else {
                    $sort_column = "rating_scale_title";
                }

                if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
                    $sort_direction = $tmp_input;
                } else {
                    $sort_direction = "ASC";
                }

                $course_permissions = $ENTRADA_USER->getCoursePermissions();

                $query = "
                    SELECT
                        *
                    FROM
                        `cbl_assessment_rating_scale`
                    WHERE
                        `deleted_date` IS NULL
                        AND `rating_scale_title` LIKE ".$db->qstr("%".$search_value."%")."
                    GROUP BY
                        `rating_scale_id`
                    ORDER BY
                         `".$sort_column."` ".$sort_direction."
                    LIMIT ".(int)($offset).", ".(int)($limit);

                $results = $db->GetAll($query);
                return $results;
        */
    }

    public static function countAllRecordsBySearchTerm($search_value, $filters = array()) {
        /*
                global $db;
                global $ENTRADA_USER;

                $course_permissions = $ENTRADA_USER->getCoursePermissions();

                $query = "
                    SELECT
                        COUNT(DISTINCT `rating_scale_id`) as `total_scales`
                    FROM
                        `cbl_assessment_rating_scale`
                    WHERE
                        `deleted_date` IS NULL
                        AND `rating_scale_title` LIKE ".$db->qstr("%". $search_value ."%")."
                ";

                $results = $db->GetRow($query);
                if ($results) {
                    return $results["total_scales"];
                }
        */
        return 0;
    }

    public static function getAllAuthors($rating_scale_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "rating_scale_id", "value" => $rating_scale_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
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

    public static function fetchRowByRatingScaleIDAuthorIDAuthorType($rating_scale_id, $author_id, $author_type) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_id", "value" => $rating_scale_id, "method" => "="),
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "=")
        ));
    }

    public static function searchByAuthor ($search_value = null) {
        global $db;

        $authors = false;

        $query = "  SELECT
						`rs`.`rating_scale_id`, `rsa`.*, `ud`.`id`, `ud`.`firstname`, `ud`.`lastname`
  					FROM
  						`cbl_assessment_rating_scale` AS `rs`
                    JOIN
                    	`cbl_assessment_rating_scale_authors` AS `rsa`
                    USING
                    	(`rating_scale_id`)
                    JOIN
                    	`".AUTH_DATABASE."`".".`user_data` AS `ud`
                    ON
                    	`rsa`.`author_id` = `ud`.`id`
                    WHERE
                    	(`ud`.`firstname` LIKE (".$db->qstr("%".$search_value."%").")
                    		OR `ud`.`lastname` LIKE (".$db->qstr("%".$search_value."%")."))
                    	AND `rsa`.`author_type` = 'proxy_id'
                    	AND `rsa`.`deleted_date` IS NULL
                    GROUP BY
                    	`rsa`.`author_id`";
        $results = $db->GetAll($query);

        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array(
                    "rating_scale_author_id" => $result["rating_scale_author_id"],
                    "rating_scale_id"	     => $result["rating_scale_id"],
                    "author_type"		     => $result["author_type"],
                    "author_id"			     => $result["author_id"],
                    "deleted_date"		     => $result["deleted_date"],
                    "updated_date"		     => $result["updated_date"],
                    "updated_by"		     => $result["updated_by"],
                ));
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