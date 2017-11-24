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
 * @author Organisation: 
 * @author Developer:  <>
 * @copyright Copyright 2015 . All Rights Reserved.
 */

class Models_Exam_Question_Bank_Folder_Authors extends Models_Base {
    protected $efauthor_id, $folder_id, $author_type, $author_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;
    protected $level, $author_name;

    protected static $table_name = "exam_question_bank_folder_authors";
    protected static $primary_key = "efauthor_id";
    protected static $default_sort_column = "folder_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->efauthor_id;
    }

    public function getEfauthorID() {
        return $this->efauthor_id;
    }

    public function getFolderID() {
        return $this->folder_id;
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
        if (isset($this->author_name)) {
            return $this->author_name;
        } else {
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
            $this->author_name = $return;
            return $return;
        }
    }

    public function setLevel($level) {
        $this->level = $level;
    }


    /* @return bool|Models_Exam_Question_Bank_Folder_Authors */
    public static function fetchRowByID($efauthor_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "efauthor_id", "value" => $efauthor_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Bank_Folder_Authors */
    public static function fetchRowByFolderIDAuthorIDAuthorType($folder_id, $author_id, $author_type) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "folder_id", "value" => $folder_id, "method" => "="),
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Bank_Folder_Authors[] */
    public static function fetchAllByFolderID($folder_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "folder_id", "value" => $folder_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Bank_Folder_Authors[] */
    public static function fetchAllByFolderIDAuthorType($folder_id, $author_type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "folder_id", "value" => $folder_id, "method" => "="),
            array("key" => "author_type", "value" => $author_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    private static function fetchParentAuthors($folder_id, $type, $author_array, $level, $deleted_date = NULL) {
        global $db;
        //function runs recursively until the parent folder is 0 which is the root.
        if ($folder_id !== 0) {

            $parent_folder_array = Models_Exam_Question_Bank_Folder_Authors::fetchAllByFolderIDAuthorType($folder_id, $type, $deleted_date);

            //if authors are found add them to the array of the specific type, check if they already exist on an earlier folder before adding
            if (isset($parent_folder_array) && is_array($parent_folder_array) && !empty($parent_folder_array)) {
                foreach ($parent_folder_array as $parent_folder) {
                    if (isset($parent_folder) && is_object($parent_folder)) {
                        if (!in_array($parent_folder->getAuthorID(), $author_array)) {
                            $parent_folder->setLevel($level);
                            $author_array[$parent_folder->getAuthorID()] = array(
                                "author_name"   => $parent_folder->getAuthorName(),
                                "author_type"   => $parent_folder->getAuthorType(),
                                "object_type"   => "folder",
                                "level"         => $level,
                                "object"        => $parent_folder
                            );
                        }
                    }
                }
            }

            $level++;

            //get the parent folder
            $current_folder = Models_Exam_Question_Bank_Folders::fetchRowByID($folder_id);
            if (isset($current_folder) && is_object($current_folder)) {
                $parent_folder_id = $current_folder->getParentFolderID();
            }

            if (isset($parent_folder_id) && $parent_folder_id != 0) {
                $author_array = Models_Exam_Question_Bank_Folder_Authors::fetchParentAuthors($parent_folder_id, $type, $author_array, $level, $deleted_date);
            } else if ($parent_folder_id == 0) {
                return $author_array;
            }

            return $author_array;

        } else {
            return $author_array;
        }
    }

    /* @return bool|Models_Exam_Question_Bank_Folder_Authors */
    public static function fetchAllInheritedByFolderID($folder_id, $sort = true, $deleted_date = NULL) {
        global $db;

        $type_array     = array("proxy_id", "organisation_id", "course_id");
        $proxy_array    = array();
        $org_array      = array();
        $course_array   = array();
        $level          = 0;
        foreach ($type_array as $type) {
            switch ($type) {
                case "proxy_id" :
                    $proxy_array    = Models_Exam_Question_Bank_Folder_Authors::fetchParentAuthors($folder_id, $type, $proxy_array, $level);
                    break;
                case "organisation_id" :
                    $org_array      = Models_Exam_Question_Bank_Folder_Authors::fetchParentAuthors($folder_id, $type, $org_array, $level);
                    break;
                case "course_id" :
                    $course_array   = Models_Exam_Question_Bank_Folder_Authors::fetchParentAuthors($folder_id, $type, $course_array, $level);
                    break;
            }

        }
        //sort the arrays by author name
        usort($proxy_array, 'Models_Exam_Question_Bank_Folder_Authors::sortByAuthorName');
        usort($org_array, 'Models_Exam_Question_Bank_Folder_Authors::sortByAuthorName');
        usort($course_array, 'Models_Exam_Question_Bank_Folder_Authors::sortByAuthorName');

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

    /* @return ArrayObject|Models_Exam_Question_Bank_Folder_Authors[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAvailableAuthors($filter_type, $folder_id, $search_value) {
        global $db, $ENTRADA_USER;

        switch ($filter_type) {
            case "organisation_id" :
                if (empty($search_value)) {
                    $query = "SELECT a.`organisation_id` AS `id`, `organisation_title` AS `fullname`, '' AS `email`
                                            FROM `".AUTH_DATABASE."`.`organisations` AS a
                                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                            ON a.`organisation_id` = b.`organisation_id`
                                            LEFT JOIN `exam_question_bank_folder_authors` AS c
                                            ON a.`organisation_id` = c.`author_id`
                                            AND c.`author_type` = 'organisation_id'
                                            AND c.`folder_id` = ".$db->qstr($folder_id)."
                                            AND c.`deleted_date` IS NULL
                                            WHERE b.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveID())."
                                            AND c.`folder_id` IS NULL
                                            GROUP BY a.`organisation_id`";
                } else {
                    $query = "SELECT a.`organisation_id` AS `id`, a.`organisation_title` AS `fullname`, '' AS `email`
                                            FROM `" . AUTH_DATABASE . "`.`organisations` AS a
                                            LEFT JOIN `exam_question_bank_folder_authors` AS b
                                            ON a.`organisation_id` = b.`author_id`
                                            AND b.`author_type` = 'organisation_id'
                                            AND b.`folder_id` = ".$db->qstr($folder_id)."
                                            AND b.`deleted_date` IS NULL
                                            WHERE a.`organisation_title` LIKE (" . $db->qstr($search_value) . ")
                                            AND b.`folder_id` IS NULL";
                }
                break;
            case "course_id" :
                $query = "SELECT a.`course_id` AS `id`, CONCAT(a.`course_code`, ' - ', a.`course_name`) AS `fullname`, '' AS `email`
                                        FROM `courses` AS a
                                        LEFT JOIN `exam_question_bank_folder_authors` AS b
                                        ON a.`course_id` = b.`author_id`
                                        AND b.`author_type` = 'course_id'
                                        AND b.`folder_id` = ".$db->qstr($folder_id)."
                                        AND b.`deleted_date` IS NULL
                                        WHERE (a.`course_code` LIKE (".$db->qstr($search_value).")
                                        OR a.`course_name` LIKE (".$db->qstr($search_value)."))
                                        AND a.`course_active` = '1'
                                        AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                                        AND b.`folder_id` IS NULL
                                        ORDER BY a.`course_code`";
                break;
            case "proxy_id" :
                $query = "SELECT a.`id`, CONCAT(a.`firstname`, ' ', a.`lastname`) AS `fullname`, a.`email`
                                        FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                                        LEFT JOIN `exam_question_bank_folder_authors` AS b
                                        ON a.`id` = b.`author_id`
                                        AND b.`author_type` = 'proxy_id'
                                        AND b.`folder_id` = ".$db->qstr($folder_id)."
                                        AND b.`deleted_date` IS NULL
                                        WHERE b.`folder_id` IS NULL
                                        HAVING `fullname` LIKE (".$db->qstr($search_value).")
                                        OR `email` LIKE (".$db->qstr($search_value).")";
                break;
        }

        return $db->GetAll($query);
    }

    /* @return ArrayObject|Models_Exam_Question_Bank_Folder_Authors[] */
    public static function fetchByAuthorTypeProxyID ($organisation_id = null, $search_value = null) {
        global $db;
        $authors = false;

        $query = "  SELECT a.`folder_id`, b.*, c.`id`, c.`firstname`, c.`lastname` FROM `exam_question_bank_folder_authors` AS a
                    JOIN `exam_question_bank_folder_authors` AS b
                    ON a.`folder_id` = b.`folder_id`
                    JOIN `". AUTH_DATABASE ."`".".`user_data` AS c
                    ON b.`author_id` = c.`id`
                    WHERE a.`organisation_id` = ?
                    AND (c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .") OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") ."))
                    AND b.`author_type` = 'proxy_id'
                    GROUP BY b.`author_id`";

        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array("efauthor_id" => $result["efauthor_id"], "folder_id" => $result["folder_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
            }
        }

        return $authors;
    }
}