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
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 */

class Models_Exam_Exam extends Models_Base {
    protected $exam_id,
        $folder_id,
        $organisation_id,
        $title,
        $description,
        $display_questions,
        $random,
        $random_answers,
        $examsoft_exam_id,
        $created_date,
        $created_by,
        $updated_date,
        $updated_by,
        $deleted_date;

    protected
        $exam_elements,
        $exam_authors,
        $total_exam_points,
        $parent_folder;

    /**
     * @var string
     */
    protected static $table_name = "exams";
    /**
     * @var string
     */
    protected static $primary_key = "exam_id";
    /**
     * @var string
     */
    protected static $default_sort_column = "exam_id";

    /**
     * @param null $arr
     */
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    /**
     * @return string
     */
    public function getDefaultSortColumn() {
        return self::$default_sort_column;
    }

    /**
     * @return mixed
     */
    public function getID() {
        return $this->exam_id;
    }

    /**
     * @return mixed
     */
    public function getExamID() {
        return $this->exam_id;
    }

    public function getFolderId() {
        return $this->folder_id;
    }

    public function setFolderId($folder_id) {
        $this->folder_id = $folder_id;
    }

    /**
     * @return mixed
     */
    public function getOrganisationID() {
        return $this->organisation_id;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getDisplayQuestions() {
        return $this->display_questions;
    }

    /**
     * @return mixed
     */
    public function getRandom() {
        return $this->random;
    }

    /**
     * @return mixed
     */
    public function getRandomAnswers() {
        return $this->random_answers;
    }
    
    /**
     * @return int
     */
    public function getExamsoftExamID() {
        return $this->examsoft_exam_id;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate() {
        return $this->created_date;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy() {
        return $this->created_by;
    }

    /**
     * @return mixed
     */
    public function getUpdatedDate() {
        return $this->updated_date;
    }

    /**
     * @return mixed
     */
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    /**
     * @return mixed
     */
    public function getDeletedDate() {
        return $this->deleted_date;
    }

    /**
     * @return bool|Models_Exam_Bank_Folders
     */
    public function getParentFolder() {
        if (NULL === $this->parent_folder) {
            $this->parent_folder = Models_Exam_Bank_Folders::fetchRowByID($this->getFolderId());
        }
        return $this->parent_folder;
    }

    /**
     * @return ArrayObject|Models_Exam_Exam_Element[]
     */
    public function getExamElements() {
        if (NULL === $this->exam_elements) {
            $this->exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($this->getID());
        }
        return $this->exam_elements;
    }

    /**
     * @return ArrayObject|Models_Exam_Exam_Author[]
     */
    public function getExamAuthors() {
        if (NULL === $this->exam_authors){
            $this->exam_authors = Models_Exam_Exam_Author::fetchAllByExamID($this->getID());
        }
        return $this->exam_authors;
    }

    public function getTotalExamPoints() {
        return $this->total_exam_points;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setTotalExamPoints($total_exam_points) {
        $this->total_exam_points = $total_exam_points;
    }

    /**
     * @param mixed $random_answers
     */
    public function setRandomAnswers($random_answers) {
        $this->random_answers = $random_answers;
    }

    /**
     * Gets an array of all the course ids that this exam has been posted
     * to. Checks for posts to learning events and course linked communities.
     * 
     * @return ArrayObject|int[]
     */
    public function getCourseIDs() {
        $course_ids = array();
        $posts = Models_Exam_Post::fetchAllByExamID($this->exam_id);
        if ($posts) {
            foreach ($posts as $post) {
                $target_type = $post->getTargetType();
                $target_id = $post->getTargetID();
                switch ($target_type) {
                    case "event" :
                        $event = Models_Event::fetchRowByID($target_id);
                        if ($event) {
                            $course_id = $event->getCourseID();
                            $course_ids[$course_id] = $course_id;
                        }
                    break;
                    case "community" :
                        $community = Models_Community_Course::fetchRowByCommunityID($target_id);
                        if ($community) {
                            $course_id = $community->getCourseID();
                            $course_ids[$course_id] = $course_id;
                        }
                    break;
                }
            }
        }
        return array_values($course_ids);
    }

    /**
     * Verify that the question (or any version of it) does not already exist on the exam
     *
     * @param Models_Exam_Question_Versions $question_version
     * @return bool
     * @throws Exception
     */
    public function hasQuestion(Models_Exam_Question_Versions $question_version) {
        $existing_exam_element = Models_Exam_Exam_Element::fetchRowByExamIDElementIDElementType($this->exam_id, $question_version->getVersionID(), "question");
        if (!$existing_exam_element) {
            $other_versions = $question_version->fetchAllRelatedVersions();
            $exam_elements = $this->getExamElements();
            foreach ($exam_elements as $exam_element) {
                $exam_element_version = $exam_element->getQuestionVersion();
                if (in_array($exam_element_version, $other_versions, true)) {
                    return true;
                }
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * @param $search_value
     * @param $limit
     * @param $offset
     * @param $sort_direction
     * @param $sort_column
     * @param array $filters
     * @param int $folder_id
     * @param int $sub_folder_search
     * @return mixed
     */
    public static function fetchAllRecordsBySearchTerm($search_value, $limit, $offset, $sort_direction, $sort_column, $filters = array(), $folder_id, $sub_folder_search) {
        global $db, $ENTRADA_USER;
        
        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "updated_date";
        }
        
        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "DESC";
        }
        
        if (isset($folder_id) && $tmp_input = clean_input($folder_id, array("trim", "int"))) {
            $folder_id = $tmp_input;
        } else {
            $folder_id = 0;
        }

        $basic_search = true;
        if ($search_value != "") {
            if (empty($filters)) {
                $basic_search = false;
            }
        }

        if ($sub_folder_search === 1) {
            $folder_array = ($folder_id === 0 ? array($folder_id) : array());
            $folder_children = Models_Exam_Bank_Folders::getChildrenFolders($folder_id, $folder_array);
            if (empty($folder_children)) {
                $folder_children = array($folder_id);
            }
        } else {
            $folder_children = array($folder_id);
        }

        $type_array     = array("proxy_id");
        $approved_folders = array();

        if ($folder_children && is_array($folder_children) && !empty($folder_children)) {
            foreach ($folder_children as $children_folder_id) {
                $authors = Models_Exam_Bank_Folder_Authors::fetchAllInheritedByFolderID($children_folder_id, true, $type_array);
                if ($authors && is_array($authors) && $type_array && is_array($type_array)) {
                    foreach ($type_array as $type) {
                        foreach ($authors[$type] as $author) {
                            if ($author && is_array($author)) {
                                if ($author["object"] && is_object($author["object"])) {
                                    if ($ENTRADA_USER->getActiveID() == $author["object"]->getAuthorID()) {
                                        if (!in_array($children_folder_id, $approved_folders)) {
                                            $approved_folders[] = $children_folder_id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $author_join = true;

        if (in_array((int)$ENTRADA_USER->getActiveID(), $approved_folders)) {
            $author_join = false;
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        if (!$basic_search) {
            $query = "  SELECT a.*, COUNT(DISTINCT b.`exam_element_id`) AS `question_count` 
                    FROM `exams` AS a
                    LEFT JOIN `exam_elements` AS b
                    ON a.`exam_id` = b.`exam_id`
                    AND b.`deleted_date` IS NULL
                    LEFT JOIN `exam_posts` AS `posts`
                    ON `posts`.`exam_id` = a.`exam_id`";
        } else {
            $query = "  SELECT a.* 
                        FROM `exams` AS a";
        }
        
        if ($filters) {
            if ($ENTRADA_USER->getActiveGroup() != "medtech" && array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_authors` AS c
                            ON a.`exam_id` = c.`exam_id`
                            AND
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND c.`author_type` = 'proxy_id'
                            AND c.`author_id`  IN (" . implode(",", array_keys($filters["author"])) . ")
                            AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation());
            } elseif ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= " LEFT JOIN `exam_authors` AS c
                            ON a.`exam_id` = c.`exam_id`
                            AND
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            } elseif (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_authors` AS c
                        ON a.`exam_id` = c.`exam_id`
                        AND c.`author_type` = 'proxy_id'
                        AND c.`author_id`  IN (" . implode(",", array_keys($filters["author"])) . ")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_authors` AS d
                            ON a.`exam_id` = d.`exam_id`
                            AND d.`author_type` = 'organisation_id'
                            AND d.`author_id`  IN (" . implode(",", array_keys($filters["organisation"])) . ")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " LEFT JOIN `exam_authors` AS e
                            ON a.`exam_id` = e.`exam_id`
                            AND e.`author_type` = 'course_id'
                            AND e.`author_id`  IN (" . implode(",", array_keys($filters["course"])) . ")";

                $query .= " LEFT JOIN `events` AS `events`
                            ON `events`.`course_id` IN (" . implode(",", array_keys($filters["course"])) . ")
                            AND `a`.`exam_id` = `posts`.`exam_id`
                            AND `posts`.`target_id` = `events`.`event_id`
                            AND `posts`.`target_type` = 'event'";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `exam_elements` AS f
                            ON a.`exam_id` = f.`exam_id`
                            AND f.`element_type` = 'question'
                            JOIN `exam_question_versions` as `eqv`
                            ON `eqv`.`version_id` = `f`.`element_id`
                            JOIN `exam_question_objectives` AS g
                            ON `eqv`.`question_id` = g.`question_id`
                            AND g.`objective_id` IN (" . implode(",", array_keys($filters["curriculum_tag"])) . ")";
            }
        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech" && $author_join) {
                $query .= " LEFT JOIN `exam_authors` AS c
                            ON a.`exam_id` = c.`exam_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            }
        }

        $query .= " WHERE a.`deleted_date` IS NULL";

        if (isset($approved_folders) && is_array($approved_folders) && !empty($approved_folders) && $ENTRADA_USER->getActiveGroup() != "medtech") {
            $folder_ids =  implode(",", $approved_folders);

            if ((isset($folder_children) && is_array($folder_children))) {
                $children_folder_ids =  implode(",", $folder_children);
            }

            $query .= " AND (
                            `a`.`folder_id` IN (" . $folder_ids . ") 
                             OR 
                            (
                               `c`.`author_type` IN ('course_id', 'proxy_id', 'organisation_id')
                                AND 
                                `c`.`deleted_date` IS NULL";

                $query .= ($children_folder_ids ? " AND `a`.`folder_id` IN (" . $children_folder_ids . ")" : "");

                $query .= "
                            )
                        )";
        } elseif (isset($folder_children) && is_array($folder_children) && !empty($folder_children)) {
            $folder_ids =  implode(",", $folder_children);
            $query .= " AND `a`.`folder_id` IN (" . $folder_ids . ")";
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= "AND (
                               `c`.`author_type` IN ('course_id', 'proxy_id', 'organisation_id')
                                AND 
                                `c`.`deleted_date` IS NULL
                            )";
            }
        } else {
            $query .= " AND `a`.`folder_id` IN (" . $db->qstr($folder_id) . ")";

            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= "AND (
                               `c`.`author_type` IN ('course_id', 'proxy_id', 'organisation_id')
                                AND
                                `c`.`deleted_date` IS NULL
                            )";
            }
        }

        if (!$basic_search) {
            $query .= " AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";
        }

        if ($filters) {
            if (array_key_exists("author", $filters) && $author_join) {
                $query .= " AND c.`deleted_date` IS NULL";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND (
                            (
                                e.`deleted_date` IS NULL
                                AND `e`.`exam_id` IS NOT NULL
                            )
                                OR `events`.`course_id` IN (". implode(",", array_keys($filters["course"])) .")
                            )";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND f.`deleted_date` IS NULL
                            AND g.`deleted_date` IS NULL";
            }
        }
        
        $query .= " GROUP BY a.`exam_id`
                    ORDER BY a.`" . (string) $sort_column . "` " . (string) $sort_direction . " 
                    LIMIT " . (int) $offset . ", " . (int) $limit;

        $results = $db->GetAll($query);

        return $results;
    }

    /**
     * @param $search_value
     * @param $course_id
     * @return mixed
     */
    public static function fetchAllRecordsBySearchTermCourseLimit($search_value, $course_id, $assessment_id = 0) {
        global $db, $ENTRADA_USER;

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT a.*, `posts`.`post_id`, `posts`.`title` AS 'post_title'
                    FROM `exams` AS a
                    JOIN `exam_posts` AS `posts`
                    ON a.`exam_id` = `posts`.`exam_id`
                    JOIN `events` AS `events`
                    ON `events`.`event_id` = `posts`.`target_id`
                    AND `posts`.`target_type` = 'event'";

        if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            $query .= " JOIN `exam_authors` AS c
                            ON a.`exam_id` = c.`exam_id`
                            AND
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
        }

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .")
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";

        $query .= " AND `events`.`course_id` = " . $db->qstr($course_id);

        if ($assessment_id != 0) {
            $query .= " AND
            (
                `posts`.`grade_book` IS NULL
                 OR
                 `posts`.`grade_book` != " . $db->qstr($assessment_id) . " 
            )";
        }

        if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            $query .= " AND c.`deleted_date` IS NULL";
        }

        $results = $db->GetAll($query);

        return $results;
    }

    /**
     * @param $search_value
     * @param array $filters
     * @param int $folder_id
     * @param int $sub_folder_search
     * @return int
     */
    public static function countAllRecordsBySearchTerm($search_value, $filters = array(), $folder_id, $sub_folder_search) {
        global $db, $ENTRADA_USER;
        
        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        if (isset($folder_id) && $tmp_input = clean_input($folder_id, array("trim", "int"))) {
            $folder_id = $tmp_input;
        } else {
            $folder_id = 0;
        }

        if ($sub_folder_search === 1) {
            $folder_array = ($folder_id === 0 ? array($folder_id) : array());
            $folder_children = Models_Exam_Bank_Folders::getChildrenFolders($folder_id, $folder_array);
            if (empty($folder_children)) {
                $folder_children = array($folder_id);
            }
        } else {
            $folder_children = array($folder_id);
        }

        $basic_search = true;
        if ($search_value != "") {
            if (empty($filters)) {
                $basic_search = false;
            }
        }

        $type_array     = array("proxy_id");
        $approved_folders = array();

        if ($folder_children && is_array($folder_children) && !empty($folder_children)) {
            foreach ($folder_children as $children_folder_id) {
                $authors = Models_Exam_Bank_Folder_Authors::fetchAllInheritedByFolderID($children_folder_id, true, $type_array);
                if ($authors && is_array($authors) && $type_array && is_array($type_array)) {
                    foreach ($type_array as $type) {
                        foreach ($authors[$type] as $author) {
                            if ($author && is_array($author)) {
                                if ($author["object"] && is_object($author["object"])) {
                                    if ($ENTRADA_USER->getActiveID() == $author["object"]->getAuthorID()) {
                                        if (!in_array($children_folder_id, $approved_folders)) {
                                            $approved_folders[] = $children_folder_id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $author_join = true;

        if (in_array((int)$ENTRADA_USER->getActiveID(), $approved_folders)) {
            $author_join = false;
        }

        if (!$basic_search) {
            $query = "  SELECT COUNT(DISTINCT a.`exam_id`) AS `total_exams`
                        FROM `exams` AS a
                        LEFT JOIN `exam_posts` AS `posts`
                        ON `posts`.`exam_id` = a.`exam_id`";
        } else {
            $query = "  SELECT COUNT(DISTINCT a.`exam_id`) AS `total_exams`
                        FROM `exams` AS a";
        }

        if ($filters) {
            if ($ENTRADA_USER->getActiveGroup() != "medtech" && array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_authors` AS c
                            ON a.`exam_id` = c.`exam_id`
                            AND
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND c.`author_type` = 'proxy_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            } elseif ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= " LEFT JOIN `exam_authors` AS c
                            ON a.`exam_id` = c.`exam_id`
                            AND
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            } elseif (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_authors` AS c
                        ON a.`exam_id` = c.`exam_id`
                        AND c.`author_type` = 'proxy_id'
                        AND c.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_authors` AS d
                            ON a.`exam_id` = d.`exam_id`
                            AND d.`author_type` = 'organisation_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["organisation"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " LEFT JOIN `exam_authors` AS e
                            ON a.`exam_id` = e.`exam_id`
                            AND e.`author_type` = 'course_id'
                            AND e.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";

                $query .= " LEFT JOIN `events` AS `events`
                            ON `events`.`course_id` IN (". implode(",", array_keys($filters["course"])) .")
                            AND `a`.`exam_id` = `posts`.`exam_id`
                            AND `posts`.`target_id` = `events`.`event_id`
                            AND `posts`.`target_type` = 'event'";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `exam_elements` AS f
                            ON a.`exam_id` = f.`exam_id`
                            AND f.`element_type` = 'question'
                            JOIN `exam_question_versions` as `eqv`
                            ON `eqv`.`version_id` = `f`.`element_id`
                            JOIN `exam_question_objectives` AS g
                            ON `eqv`.`question_id` = g.`question_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech" && $author_join) {
                $query .= " LEFT JOIN `exam_authors` AS c
                            ON a.`exam_id` = c.`exam_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            }
        }

        $query .= " WHERE a.`deleted_date` IS NULL";

        if (isset($approved_folders) && is_array($approved_folders) && !empty($approved_folders) && $ENTRADA_USER->getActiveGroup() != "medtech") {
            $folder_ids =  implode(",", $approved_folders);

            if ((isset($folder_children) && is_array($folder_children))) {
                $children_folder_ids =  implode(",", $folder_children);
            }

            $query .= " AND (
                            `a`.`folder_id` IN (" . $folder_ids . ") 
                             OR 
                            (
                               `c`.`author_type` IN ('course_id', 'proxy_id', 'organisation_id')
                                AND 
                                `c`.`deleted_date` IS NULL";

                $query .= ($children_folder_ids ? " AND `a`.`folder_id` IN (" . $children_folder_ids . ")" : "");

                $query .= "
                            )
                        )";
        } elseif (isset($folder_children) && is_array($folder_children) && !empty($folder_children)) {
            $folder_ids =  implode(",", $folder_children);
            $query .= " AND `a`.`folder_id` IN (" . $folder_ids . ")";
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= "AND (
                               `c`.`author_type` IN ('course_id', 'proxy_id', 'organisation_id')
                                AND 
                                `c`.`deleted_date` IS NULL
                            )";
            }
        } else {
            $query .= " AND `a`.`folder_id` IN (" . $db->qstr($folder_id) . ")";
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= "AND (
                               `c`.`author_type` IN ('course_id', 'proxy_id', 'organisation_id')
                                AND 
                                `c`.`deleted_date` IS NULL
                            )";
            }
        }

        if (!$basic_search) {
            $query .= " AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";
        }

        if ($filters) {
            if (array_key_exists("author", $filters) && $author_join) {
                $query .= " AND c.`deleted_date` IS NULL";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND (
                            (
                                e.`deleted_date` IS NULL
                                AND `e`.`exam_id` IS NOT NULL
                            )
                                OR `events`.`course_id` IN (". implode(",", array_keys($filters["course"])) .")
                            )";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND f.`deleted_date` IS NULL
                            AND g.`deleted_date` IS NULL";
            }
        }

        $results = $db->GetRow($query);
        if ($results) {
            return $results["total_exams"];
        }
        return 0;
    }

    /* @return bool|Models_Exam_Exam */
    public static function fetchRowByID($exam_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    /* @return bool|Models_Exam_Exam */
    public static function fetchRowByExamsoftExamID($examsoft_exam_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "examsoft_exam_id", "value" => $examsoft_exam_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")))
        );
    }

    /* @return ArrayObject|Models_Exam_Exam[] */
    public static function fetchAllByFolderID($folder_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "folder_id", "value" => $folder_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")))
        );
    }

    /* @return ArrayObject|Models_Exam_Exam[] */
    public static function fetchAllBySearchTerm($search_value) {
        global $db;
        $query = "SELECT * FROM `exams` AS e WHERE e.`title` LIKE " . $db->qstr("%". $search_value ."%") . "
                  AND e.`deleted_date` IS NULL";
        $results = $db->GetAll($query);
        $exams = array();
        if ($results) {
            foreach ($results as $exam) {
                $exams[] = new self($exam);
            }
        }
        return $exams;
    }

    /**
     * @param array $filters
     */
    public static function saveFilterPreferences($filters = array()) {
        global $db;
        
        if (!empty($filters)) {
            foreach ($filters as $filter_type => $filter_targets) {
                foreach ($filter_targets as $target) {
                    $target_label = "";
                    $target = clean_input($target, array("int"));
                    switch ($filter_type) {
                        case "curriculum_tag" :
                            $objective = Models_Objective::fetchRow($target);
                            if ($objective) {
                                $target_label = $objective->getName();
                            }
                        break;
                        case "course" :
                            $course = Models_Course::get($target);
                            if ($course) {
                                $target_label = $course->getCourseName();
                            }
                        break;
                        case "author" :
                            $query = "SELECT CONCAT(`firstname`, ' ', `lastname`) AS fullname FROM `". AUTH_DATABASE ."`.`user_data` WHERE `id` = ?";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["fullname"];
                            }
                        break;
                        case "organisation" :
                            $query = "SELECT * FROM `". AUTH_DATABASE ."`.`organisations` WHERE `organisation_id` = ?";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["organisation_title"];
                            }
                        break;
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }

    /* @return ArrayObject|Models_Exam_Exam[] */
    public static function fetchAllByOwner($proxy_id = null, $search_value = "") {
        global $db, $ENTRADA_USER;
        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        $exams = false;
        
        $query = "SELECT * FROM `exams` AS a";
        
        if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            $query .= " JOIN `exam_authors` AS b
                        ON a.`exam_id` = b.`exam_id` 
                        AND 	
                        ("
                            .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                            .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                            .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                            (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                            (b.`author_type` = 'organisation_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                        )
                        AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
        }
        
        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";
        
        if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            $query .= " AND b.`deleted_date` IS NULL";
        }

        $query .= " ORDER BY a.`updated_date` DESC";
        
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $exam) {
                $exams[] = new self($exam);
            }
        }
        return $exams;
    }
    
    /**
     * Returns an array of exam_ids that have been updated since the given
     * time.
     * 
     * @global ADODB $db
     * @param int $updated_since
     * @return ArrayObject|int[]
     */
    public static function fetchAllRecentExamIds($updated_since) {
        global $db;
        $query = "
            SELECT `exam_id`
            FROM `exams`
            WHERE `updated_date` >= ".$db->qstr($updated_since)."
            ORDER BY `updated_date` DESC";
        $results = $db->GetAll($query);
        $exam_ids = array();
        if ($results) {
            foreach ($results as $result) {
                $exam_ids[] = (int)$result["exam_id"];
            }
        }
        return $exam_ids;
    }

    /**
     * @return int
     */
    public function countExamQuestions() {
        $count = 0;
        $elements = $this->getExamElements();
        $text_questiontype = Models_Exam_Lu_Questiontypes::fetchRowByShortname("text");

        if (isset($text_questiontype) && is_object($text_questiontype)) {
            $text_questiontype_id = $text_questiontype->getID();
        }

        if (isset($elements) && is_array($elements) && !empty($elements)) {
            foreach ($elements as $element) {
                if (isset($element) && is_object($element)) {
                    switch ($element->getElementType()) {
                        case "question" :
                            $question = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                            if (isset($question) && is_object($question) && $question->getQuestionType() != $text_questiontype_id) {
                                $count++;
                            }
                            break;
                        case "group" :
                            //get group question count
                            $group = Models_Exam_Group::fetchRowByID($element->getElementID());
                            if (isset($group) && is_object($group)) {
                                $group_questions = $group->getGroupQuestions();
                                if (isset($group_questions) && is_array($group_questions) && !empty($group_questions)) {
                                    foreach ($group_questions as $group_question) {
                                        if (isset($group_question) && is_object($group_question)) {
                                            $question = $group_question->getQuestionVersion();
                                            if (isset($question) && is_object($question) && $question->getQuestionType() != $text_questiontype_id) {
                                                $count++;
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * @return int
     */

    public function countPosts() {
        $count = 0;
        $posts = Models_Exam_Post::fetchAllByExamIDNoPreview($this->getExamID());

        if (isset($posts) && is_array($posts)) {
            $count = count($posts);
        }

        return $count;
    }

    /**
     * Adds an Exam Element to the Exam
     * Optionally insert into a specific position by providing a value for @param $position
     *
     * @param Models_Exam_Exam_Element $element
     * @param null $position
     * @return $this
     * @throws Exception
     */
    public function addElement(Models_Exam_Exam_Element $element, $position = NULL) {
        if ($this->hasQuestion($element->getQuestionVersion())) {
            throw new Exception('The question (or another version of it) already exists on this exam');
        }

        $element_order = (NULL === $position) ? $element::fetchNextOrder($this->getID()) : $position;
        $element->setExamID($this->getID());
        $element->setOrder($element_order);

        $this->exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($this->getID()); //Added to prevent the state of exam_elements from being outdated
        //Insert the new exam element at the specified position
        array_splice($this->exam_elements, $element_order, 0, array($element));

        //If the position is not specified, the order for the rest of the collection doesn't need to be updated, so just persist it to the DB
        if (NULL === $position) {
            if (!$element->getID()) {
                $element->insert();
            } else {
                $element->update();
            }
        } else {
            foreach ($this->exam_elements as $order => $exam_element) {
                $exam_element->setExamID($this->getID());
                $exam_element->setOrder($order);
                $exam_element->setUpdatedDate(time());
                if (!$exam_element->getID()){
                    $exam_element->insert();
                } else {
                    $exam_element->update();
                }
            }
        }

        return $this;
    }

    public static function get_difficulty_index($responses) {
        if (!count($responses)) {
            return "N/A";
        }
        $num_correct = 0;
        foreach ($responses as $response) {
            if ($response->getScore()) {
                $num_correct++;
            }
        }
        return round($num_correct / count($responses), 2);
    }

    public static function get_discrimination_index($responses, $top_27, $bottom_27, $letter_func) {
        $num_top_correct = 0;
        $num_bottom_correct = 0;
        $num_top = 0;
        $num_bottom = 0;
        foreach ($responses as $response) {
            $correct = $letter_func($response);
            if (in_array($response->getExamProgressID(), $top_27)) {
                if ($correct) {
                    $num_top_correct++;
                }
                $num_top++;
            }
            if (in_array($response->getExamProgressID(), $bottom_27)) {
                if ($correct) {
                    $num_bottom_correct++;
                }
                $num_bottom++;
            }
        }
        if ($num_top && $num_bottom) {
            return round(($num_top_correct / $num_top) - ($num_bottom_correct / $num_bottom), 2);
        } else {
            return "N/A";
        }
    }

    public static function get_percent_correct($responses, $progress_ids, $letter_func) {
        $num_correct = 0;
        $num_total = 0;
        foreach ($responses as $response) {
            if (in_array($response->getExamProgressID(), $progress_ids)) {
                $correct = $letter_func($response);
                if ($correct) {
                    $num_correct++;
                }
                $num_total++;
            }
        }
        return $num_total ? round(100 * $num_correct / $num_total, 2) : false;
    }

    public static function get_point_biserial_correlation($submissions, $elem, $stdev, $letter_func) {
        if (!count($submissions) || !$stdev) {
            return "N/A";
        }
        $correct_scores = array();
        $incorrect_scores = array();
        foreach ($submissions as $submission) {
            $response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($submission->getID(), $elem->getID());
            if (!$response) {
                continue;
            }
            if ($letter_func($response)) {
                $correct_scores[] = $submission->getExamPoints();
            } else {
                $incorrect_scores[] = $submission->getExamPoints();
            }
        }
        $n1 = count($correct_scores);
        $n0 = count($incorrect_scores);
        $m1 = $n1 ? array_sum($correct_scores) / $n1 : 0;
        $m0 = $n0 ? array_sum($incorrect_scores) / $n0 : 0;
        return $n1 + $n0 ? round((($m1 - $m0) / $stdev) * sqrt(($n1 * $n0) / pow($n1 + $n0, 2)), 2) : "N/A";
    }

    /**
     *
     * This function calculates the KR20 score for a group of exam questions.
     *
     * @param $exam_elements // elements to calculate against
     * @param array $scores // this is an array of the calculated percentages and not the raw points
     * @param float $mean // this is the mean of the calculated scores and not the raw points
     * @return float|string
     */
    public static function get_kr20($exam_elements, $scores, $mean) {
        $kr20_summation = 0;
        foreach ($exam_elements as $elem) {
            $responses = Models_Exam_Progress_Responses::fetchAllByExamElementID($elem->getID());
            $total = count($responses);
            $total_correct = 0;
            foreach ($responses as $response) {
                if ($response->getScore()) {
                    $total_correct++;
                }
            }
            $p = $total ? $total_correct / $total : 1;
            $q = 1 - $p;
            $kr20_summation += $p * $q;
        }
        // @todo take a look at this as a single score of 100 will result in N/A being returned.
        if (count($scores)) {
            $kr20_variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $scores)) / count($scores);
        }

        if ($kr20_variance) {
            return (count($exam_elements) / (count($exam_elements) - 1)) * (1 - ($kr20_summation / $kr20_variance));
        } else {
            return "N/A";
        }
    }
}
