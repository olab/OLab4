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
 * A Model to handle question authors
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Authors extends Models_Base {
    protected $eqauthor_id, $question_id, $version_id, $author_type, $author_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "exam_question_authors";
    protected static $primary_key = "eqauthor_id";
    protected static $default_sort_column = "question_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->eqauthor_id;
    }

    public function getEqauthorID() {
        return $this->eqauthor_id;
    }

    public function getQuestionID() {
        return $this->question_id;
    }

    public function getVersion_ID() {
        return $this->version_id;
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

    /* @return bool|Models_Exam_Question_Authors */
    public static function fetchRowByID($eqauthor_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eqauthor_id", "value" => $eqauthor_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($version_id = NULL, $active = 1) {
        $constraints = array(
            array("key" => "eqauthor_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        );

        if (!is_null($version_id)) {
            $constraints[] = array("key" => "version_id", "value" => (int) $version_id, "method" => "=");
        }

        $self = new self();
        return $self->fetchAll($constraints);
    }

    /* @return ArrayObject|Models_Exam_Question_Authors[] */
    public static function fetchAllByVersionIDAuthorType($version_id, $author_type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Authors */
    public static function fetchRowByVersionIDAuthorIDAuthorType($version_id, $author_id, $author_type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function getAuthorName() {
        global $db;

        $return = false;
        switch ($this->author_type) {
            case "proxy_id" :
                $user = User::fetchRowByID($this->author_id, true, null, 1);
                if ($user) {
                    $return = $user->getFullname(true);
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

    public static function fetchAvailableAuthors($filter_type, $question_id, $search_value) {
        global $db, $ENTRADA_USER;

        switch ($filter_type) {
            case "organisation_id" :
                if (empty($search_value)) {
                    $query = "  SELECT a.`organisation_id` AS `id`, `organisation_title` AS `fullname`, '' AS `email`
                                FROM `".AUTH_DATABASE."`.`organisations` AS a
                                JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                ON a.`organisation_id` = b.`organisation_id`
                                LEFT JOIN `exam_question_authors` AS c
                                ON a.`organisation_id` = c.`author_id`
                                AND c.`author_type` = 'organisation_id'
                                AND c.`question_id` = ".$db->qstr($question_id)."
                                AND c.`deleted_date` IS NULL
                                WHERE b.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveID())."
                                AND c.`question_id` IS NULL
                                GROUP BY a.`organisation_id`";
                } else {
                    $query = "  SELECT a.`organisation_id` AS `id`, a.`organisation_title` AS `fullname`, '' AS `email`
                                FROM `" . AUTH_DATABASE . "`.`organisations` AS a
                                LEFT JOIN `exam_question_authors` AS b
                                ON a.`organisation_id` = b.`author_id`
                                AND b.`author_type` = 'organisation_id'
                                AND b.`question_id` = ".$db->qstr($question_id)."
                                AND b.`deleted_date` IS NULL
                                WHERE a.`organisation_title` LIKE (" . $db->qstr($search_value) . ")
                                AND b.`question_id` IS NULL";
                }
                break;
            case "course_id" :
                $query = "  SELECT a.`course_id` AS `id`, CONCAT(a.`course_code`, ' - ', a.`course_name`) AS `fullname`, '' AS `email`
                            FROM `courses` AS a
                            LEFT JOIN `exam_question_authors` AS b
                            ON a.`course_id` = b.`author_id`
                            AND b.`author_type` = 'course_id'
                            AND b.`question_id` = ".$db->qstr($question_id)."
                            AND b.`deleted_date` IS NULL
                            WHERE (a.`course_code` LIKE (".$db->qstr($search_value).")
                            OR a.`course_name` LIKE (".$db->qstr($search_value)."))
                            AND a.`course_active` = '1'
                            AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                            AND b.`question_id` IS NULL
                            ORDER BY a.`course_code`";
                break;
            case "proxy_id" :
                $query = "  SELECT a.`id`, CONCAT(a.`firstname`, ' ', a.`lastname`) AS `fullname`, a.`email`
                            FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                            LEFT JOIN `exam_question_authors` AS b
                            ON a.`id` = b.`author_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`question_id` = ".$db->qstr($question_id)."
                            AND b.`deleted_date` IS NULL
                            WHERE b.`question_id` IS NULL
                            HAVING `fullname` LIKE (".$db->qstr($search_value).")
                            OR `email` LIKE (".$db->qstr($search_value).")";
                break;
        }

        return $db->GetAll($query);
    }

    /* @return ArrayObject|Models_Exam_Question_Authors[] */
    public static function fetchAllByQuestionID ($question_id = null, $organisation_id = null) {
        global $db;

        $query = "  SELECT a.*, b.* FROM `exam_questions` AS a
                    JOIN `exam_question_authors` AS b
                    ON a.`question_id` = b.`question_id`
                    WHERE a.`question_id` = ?
                    AND a.`organisation_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL";

        $authors = false;
        $results = $db->GetAll($query, array($question_id, $organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array("eqauthor_id" => $result["eqauthor_id"], "question_id" => $result["question_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
            }
        }

        return $authors;
    }

    /* @return ArrayObject|Models_Exam_Question_Authors[] */
    public static function fetchAllByVersionID ($version_id = null, $organisation_id = null) {
        global $db;

        $query = "  SELECT a.*, b.* FROM `exam_question_versions` AS a
                    JOIN `exam_question_authors` AS b
                    ON a.`version_id` = b.`version_id`
                    WHERE a.`version_id` = ?
                    AND a.`organisation_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL";

        $authors = false;
        $results = $db->GetAll($query, array($version_id, $organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array("eqauthor_id" => $result["eqauthor_id"], "question_id" => $result["question_id"], "version_id" => $result["version_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
            }
        }

        return $authors;
    }

    /* @return ArrayObject|Models_Exam_Question_Authors[] */
    public static function fetchByAuthorTypeProxyID ($organisation_id = null, $search_value = null) {
        global $db;
        $authors = false;

        $query = "  SELECT a.`question_id`, a.`version_id`, b.*, c.`id`, c.`firstname`, c.`lastname`
                    FROM `exam_question_versions` AS a
                    JOIN `exam_question_authors` AS b
                    ON a.`version_id` = b.`version_id`
                    JOIN `". AUTH_DATABASE ."`".".`user_data` AS c
                    ON b.`author_id` = c.`id`
                    WHERE a.`organisation_id` = ?
                    AND (c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .") OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") ."))
                    AND b.`author_type` = 'proxy_id'
                    GROUP BY b.`author_id`";

        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array("eqauthor_id" => $result["eqauthor_id"], "question_id" => $result["question_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
            }
        }

        return $authors;
    }

    private static function fetchAllByVersionIdFormatted($version_id, $type, $author_array, $deleted_date = NULL) {
        global $db;
        $authors = Models_Exam_Question_Authors::fetchAllByVersionIDAuthorType($version_id, $type, $author_array, $deleted_date);

        //if authors are found add them to the array of the specific type, check if they already exist on an earlier folder before adding
        if (isset($authors) && is_array($authors) && !empty($authors)) {
            foreach ($authors as $author) {
                if (isset($author) && is_object($author)) {
                    if (!in_array($author->getAuthorID(), $author_array)) {
                        $author_array[$author->getAuthorID()] = array(
                            "author_name"   => $author->getAuthorName(),
                            "author_type"   => $author->getAuthorType(),
                            "level"         => 0,
                            "object_type"   => "question",
                            "object"        => $author
                        );
                    }
                }
            }
        }

        return $author_array;
    }

    /* @return bool|Models_Exam_Question_Authors */
    public static function fetchAllByVersionIdGroupedByType($version_id, $sort = true, $deleted_date = NULL) {
        global $db;

        $type_array     = array("proxy_id", "organisation_id", "course_id");
        $proxy_array    = array();
        $org_array      = array();
        $course_array   = array();
        $level          = 0;
        foreach ($type_array as $type) {
            switch ($type) {
                case "proxy_id" :
                    $proxy_array    = Models_Exam_Question_Authors::fetchAllByVersionIdFormatted($version_id, $type, $proxy_array, $deleted_date);
                    break;
                case "organisation_id" :
                    $org_array      = Models_Exam_Question_Authors::fetchAllByVersionIdFormatted($version_id, $type, $org_array, $deleted_date);
                    break;
                case "course_id" :
                    $course_array   = Models_Exam_Question_Authors::fetchAllByVersionIdFormatted($version_id, $type, $course_array, $deleted_date);
                    break;
            }

        }
        //sort the arrays by author name
        usort($proxy_array, 'Models_Exam_Question_Authors::sortByAuthorName');
        usort($org_array, 'Models_Exam_Question_Authors::sortByAuthorName');
        usort($course_array, 'Models_Exam_Question_Authors::sortByAuthorName');

        $author_array =  array(
            "proxy_id"          => $proxy_array,
            "organisation_id"   => $org_array,
            "course_id"         => $course_array
        );

        return $author_array;
    }

    public static function sortByAuthorName($a, $b) {
        return strcmp($a["author_name"], $b["author_name"]);
    }
}