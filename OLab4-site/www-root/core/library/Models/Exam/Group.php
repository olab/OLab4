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

class Models_Exam_Group extends Models_Base {
    protected $group_id, $organisation_id, $group_title, $group_description, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;
    protected $group_questions, $group_authors;

    protected static $table_name = "exam_groups";
    protected static $default_sort_column = "group_title";
    protected static $primary_key = "group_id";
    protected $display_columns = array(1 => "group_id", 2 => "group_title", 3 => "group_description");

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->group_id;
    }

    public function getGroupID() {
        return $this->group_id;
    }

    public function getOrganisationId() {
        return $this->organisation_id;
    }

    public function getGroupTitle() {
        return $this->group_title;
    }

    public function getGroupDescription() {
        return $this->group_description;
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

    public function getDisplayColumns() {
        return $this->display_columns;
    }

    public function setGroupDescription($group_description) {
        $this->group_description = $group_description;
    }

    public function setGroupTitle($group_title) {
        $this->group_title = $group_title;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    /* @return ArrayObject|Models_Exam_Group_Question[] */
    public function getGroupQuestions() {
        if (NULL === $this->group_questions) {
            $this->group_questions = Models_Exam_Group_Question::fetchAllByGroupID($this->getID());
        }

        return $this->group_questions;
    }

    /* @return ArrayObject|Models_Exam_Group_Author[] */
    public function getGroupAuthors() {
        if (NULL === $this->group_authors) {
            $this->group_authors = Models_Exam_Group_Author::fetchAllByGroupID($this->getID());
        }

        return $this->group_authors;
    }

    /**
     * duplicate the exam group, authors, and questions
     *
     * @param Models_Exam_Group $exam_group
     * @return Models_Exam_Group $new_exam_group
     */
    public static function duplicateGroup($exam_group) {
        global $ENTRADA_USER;
        if ($exam_group && is_object($exam_group)) {
            $exam_group_questions   = $exam_group->getGroupQuestions();
            $exam_group_authors     = $exam_group->getGroupAuthors();

            $exam_group_array = $exam_group->toArray();
            if ($exam_group_array && is_array($exam_group_array)) {
                unset($exam_group_array["group_id"]);
                $new_exam_group = new Models_Exam_Group($exam_group_array);
                $new_exam_group->setUpdatedBy($ENTRADA_USER->getID());
                $new_exam_group->setUpdatedDate(time());
                if ($new_exam_group->insert()) {
                    $new_group_id = $new_exam_group->getID();

                    if ($exam_group_questions && is_array($exam_group_questions) && !empty($exam_group_questions)) {
                        foreach ($exam_group_questions as $exam_group_question) {
                            $exam_group_question_array = $exam_group_question->toArray();
                            unset($exam_group_question_array["egquestion_id"]);
                            $new_exam_group_question = new Models_Exam_Group_Question($exam_group_question_array);
                            $new_exam_group_question->setGroupId($new_group_id);
                            $new_exam_group_question->setUpdatedBy($ENTRADA_USER->getID());
                            $new_exam_group_question->setUpdatedDate(time());
                            if (!$new_exam_group_question->insert()) {
                                // error
                            }
                        }
                    }

                    if ($exam_group_authors && is_array($exam_group_authors) && !empty($exam_group_authors)) {
                        foreach ($exam_group_authors as $exam_group_author) {
                            $exam_group_author_array = $exam_group_author->toArray();
                            unset($exam_group_author_array["egauthor_id"]);
                            $new_exam_author_question = new Models_Exam_Group_Author($exam_group_author_array);
                            $new_exam_author_question->setGroupId($new_group_id);
                            $new_exam_author_question->setUpdatedBy($ENTRADA_USER->getID());
                            $new_exam_author_question->setUpdatedDate(time());
                            if (!$new_exam_author_question->insert()) {
                                // error
                            }
                        }
                    }

                    return $new_exam_group;
                }
            }
        }
        return false;
    }

    /**
     * Verify that the question (or any version of it) does not already exist in the group
     *
     * @param Models_Exam_Question_Versions $question_version
     * @return bool
     * @throws Exception
     */
    public function hasQuestion(Models_Exam_Question_Versions $question_version) {
        $existing_group_element = Models_Exam_Group_Question::fetchRowByVersionIDGroupID($question_version->getVersionID(), $this->group_id);
        if (!$existing_group_element) {
            $other_versions = $question_version->fetchAllRelatedVersions();
            $group_questions = $this->getGroupQuestions();
            foreach ($group_questions as $group_question) {
                $group_question_version = $group_question->getQuestionVersion();
                if (in_array($group_question_version , $other_versions, true)) {
                    return true;
                }
            }
        } else {
            return true;
        }

        return false;
    }

    /* @return bool|Models_Exam_Group */
    public static function fetchRowByID($group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Group[] */
    public static function fetchAllRecords($deleted_date = NULL, $sort_column = NULL, $sort_direction = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))), "=", "AND", $sort_column, $sort_direction);
    }

    public static function fetchAllRecordsBySearchTerm($search_value, $limit, $offset, $sort_direction, $sort_column, $filters = array()) {
        global $db, $ENTRADA_USER;
        
        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "group_title";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        
        $query = "  SELECT a.* " . ($ENTRADA_USER->getActiveGroup() != "medtech" ? ", b.`author_type`, b.`author_id` " : " ") ."
                    FROM `exam_groups` AS a";
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_group_authors` AS b
                            ON a.`group_id` = b.`group_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_group_authors` AS c
                            ON a.`exam_id` = c.`exam_id`
                            AND c.`author_type` = 'organisation_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["organisation"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `exam_group_authors` AS d
                            ON a.`group_id` = d.`group_id`
                            AND d.`author_type` = 'course_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `exam_group_questions` AS e
                            ON a.`group_id` = e.`group_id`
                            JOIN `exam_question_objectives` AS f
                            ON e.`question_id` = f.`question_id`
                            AND f.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= " JOIN `exam_group_authors` AS b
                            ON a.`group_id` = b.`group_id` 
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
        }

        $query .= " WHERE a.`deleted_date` IS NULL";
        if ($search_value && $search_value != "") {
            $query .= " AND
                (
                    (
                        a.`group_title` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR a.`group_description` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )
                )";
        }
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL
                            AND f.`deleted_date` IS NULL";
            }
        } else if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            $query .= " AND b.`deleted_date` IS NULL";
        }
        

        $query .= " GROUP BY a.`group_id`
                    ORDER BY a.`" . $sort_column . "` " . $sort_direction . "
                    LIMIT " . (int) $offset . ", " . (int) $limit;

        $results = $db->GetAll($query);
        return $results;
    }
    
    public static function countAllRecordsBySearchTerm($search_value, $filters = array()) {
        global $db;
        global $ENTRADA_USER;
        
        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT COUNT(DISTINCT a.group_id) as total_groups
                    FROM `exam_groups` AS a";
                    
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_group_authors` AS b
                            ON a.`group_id` = b.`group_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_group_authors` AS c
                            ON a.`group_id` = c.`group_id`
                            AND c.`author_type` = 'organisation_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["organisation"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `exam_group_authors` AS d
                            ON a.`group_id` = d.`group_id`
                            AND d.`author_type` = 'course_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `exam_group_questions` AS e
                            ON a.`group_id` = e.`group_id`
                            JOIN `exam_question_objectives` AS f
                            ON e.`question_id` = f.`question_id`
                            AND f.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= " JOIN `exam_group_authors` AS c
                            ON a.`group_id` = c.`group_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (c.`author_type` = 'organisation_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            }
        }

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`group_title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`group_description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL
                            AND f.`deleted_date` IS NULL";
            }
        } else if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            $query .= " AND c.`deleted_date` IS NULL";
        }
        
        $results = $db->GetRow($query);
        if ($results) {
            return $results["total_groups"];
        }
        return 0;
    }
    
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
                            $query = "SELECT * FROM `". AUTH_DATABASE ."`.`organisations` WHERE `orgainisation_id` = ?";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["organisation_title"];
                            }
                        break;
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }
}