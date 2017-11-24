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
 * A Model for handling questions versions
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Versions extends Models_Base {
    protected
        $version_id,
        $question_id,
        $version_count,
        $questiontype_id,
        $question_text,
        $question_description,
        $question_rationale,
        $question_correct_text,
        $question_code,
        $grading_scheme,
        $organisation_id,
        $created_date,
        $created_by,
        $updated_date,
        $updated_by,
        $deleted_date,
        $updater_user,
        $examsoft_id,
        $examsoft_images_added,
        $examsoft_flagged;

    protected $folder_id;
    protected $question;
    protected $question_type;
    protected $question_answers;
    protected $question_objectives;
    protected $question_tags;
    protected $parent_folder;
    protected $match_stems;

    protected static $table_name = "exam_question_versions";
    protected static $primary_key = "version_id";
    protected static $default_sort_column = "version_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->version_id;
    }

    public function getQuestionID() {
        return $this->question_id;
    }

    public function getVersionID() {
        return $this->version_id;
    }

    public function getVersionCount() {
        return $this->version_count;
    }

    public function getQuestionText() {
        return $this->question_text;
    }

    public function getQuestionDescription() {
        return $this->question_description;
    }

    public function getRationale() {
        return $this->question_rationale;
    }

    public function getCorrectText() {
        return $this->question_correct_text;
    }

    public function getQuestionCode() {
        return $this->question_code;
    }

    public function getGradingScheme() {
        return $this->grading_scheme;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getQuestiontypeID() {
        return $this->questiontype_id;
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
    
    public function getExamsoftId() {
        return $this->examsoft_id;
    }
    
    public function getExamsoftImagesAdded() {
        return $this->examsoft_images_added;
    }
    
    public function getExamsoftFlagged() {
        return $this->examsoft_flagged;
    }

    public function setQuestionID($id) {
        $this->question_id = $id;
    }

    public function setVersionID($id) {
        $this->version_id = $id;
    }

    public function setQuestionText($question_text) {
        $this->question_text = $question_text;
    }

    public function setQuestionDescription($question_description) {
        $this->question_description = $question_description;
    }

    public function setRationale($question_rationale) {
        $this->question_rationale = $question_rationale;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->$updated_by = $updated_by;
    }

    public function setQuestionCode($question_code) {
        $this->question_code = $question_code;
    }

    public function setQuestiontypeID($questiontype_id) {
        $this->questiontype_id = $questiontype_id;
    }

    public function setGradingScheme($scheme) {
        $this->grading_scheme = $scheme;
    }

    public function setCorrectText($text) {
        $this->question_correct_text = $text;
    }
    
    public function setExamsoftID($examsoft_id) {
        $this->examsoft_id = $examsoft_id;
    }
    
    public function setExamsoftImagesAdded($examsoft_images_added) {
        $this->examsoft_images_added = $examsoft_images_added;
    }
    
    public function setExamsoftFlagged($examsoft_flagged) {
        $this->examsoft_flagged = $examsoft_flagged;
    }

    public function getFolderID() {
        if (NULL === $this->folder_id) {
            $question = $this->getQuestion();
            if ($question && is_object($question)) {
                $this->folder_id = (int)$question->getFolderID();
            }
        }
        return $this->folder_id;
    }

    public function setFolderID($folder_id) {
        $this->folder_id = $folder_id;
        $question = $this->getQuestion();
        if ($question && is_object($question)) {
            $question->setFolderID($folder_id);
        }
    }

    public function updateQuestionObject() {
        $question = $this->getQuestion();
        $update = false;
        if ($question && is_object($question)) {
            if ($question->update()) {
                $update = true;
            }
        }
        return $update;
    }

    /* @return bool|Models_Exam_Questions */
    public function getQuestion() {
        if (NULL === $this->question) {
            $this->question = Models_Exam_Questions::fetchRowByID($this->getQuestionID());
        }

        return $this->question;
    }

    /* @return bool|User */
    public function getUpdaterUser() {
        if (NULL === $this->updater_user) {
            $this->updater_user = User::fetchRowByID($this->updated_by, null, null, 1);
        }

        return $this->updater_user;
    }
    
    public function getAdjustedMultipleChoiceCorrectText($exam_element_id, $exam_id) {
        $correct_text = array();
        $answers = $this->getQuestionAnswers();
        foreach ($answers as $answer) {
            if ($answer->getAdjustedCorrect($exam_element_id, $exam_id)) {
                $letter = chr($answer->getOrder() + ord('A') - 1);
                $correct_text[] = $letter;
            }
        }
        return implode(", ", $correct_text);
    }

    public function fetchAllRelatedVersions($unset = 1) {
        $versions = self::fetchAllByQuestionID($this->question_id);
        if ($versions && is_array($versions) && count($versions > 1) && $unset == 1) {
            foreach ($versions as $key => $version) {
                if ($this->getVersionID() === $version->getVersionID()) {
                    unset($versions[$key]);
                }
            }
        }
        return $versions;
    }

    public function checkHighestVersion($unset = 1) {
        $versions = self::fetchAllRelatedVersions($unset);
        $highest_version = 0;
        $version_ids = array();
        if ($versions && is_array($versions) && count($versions > 1)) {
            foreach ($versions as $key => $version) {
                $version_id = $version->getVersionID();
                if (!array_key_exists($version_id, $version_ids)) {
                    $version_ids[] = $version_id;
                }
            }

            if ((int)$this->getVersionID() == (int)max($version_ids)) {
                $highest_version = 1;
            }
        } else {
            // only version is this one so return 1
            $highest_version = 1;
        }
        return $highest_version;
    }


    public function getHighestVersionNumber($unset = 0) {
        $versions = self::fetchAllRelatedVersions($unset);
        $highest_version = 0;
        $version_ids = array();
        if ($versions && is_array($versions) && count($versions > 1)) {
            foreach ($versions as $key => $version) {
                $version_id = $version->getVersionCount();
                if (!array_key_exists($version_id, $version_ids)) {
                    $version_ids[] = $version_id;
                }
            }
            $highest_version = max($version_ids);
        } else {
            // only version is this one so return 1
            $highest_version = 1;
        }
        return $highest_version;
    }

    /* @return bool|Models_Exam_Question_Versions */
    public static function fetchRowByQuestionID($question_id, $version_id = NULL, $deleted_date = NULL) {
        global $db;
        $query = "  SELECT a.*
                    FROM `exam_question_versions` as a
                    WHERE a.`question_id` = " . $db->qstr($question_id) ."
                    ".($version_id ? "AND a.`version_id` = ".$db->qstr($version_id) : "")."
                    AND a.`deleted_date` ".($deleted_date ? "<= ".$db->qstr($deleted_date) : "IS NULL")."
                    ORDER BY a.`version_id` DESC
                    LIMIT 1";
        $results = $db->GetRow($query);

        return new self($results);
    }

    /* @return ArrayObject|Models_Exam_Question_Versions[] */
    public static function fetchAllByQuestionID($question_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "question_id", "value" => $question_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")))
        );
    }

    /* @return ArrayObject|Models_Exam_Question_Versions[] */
    public static function fetchAllByQuestionIDFolderID($question_id, $folder_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "question_id", "value" => $question_id, "method" => "="),
                array("key" => "folder_id", "value" => $folder_id, "method" => "="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")))
        );
    }

    /* @return bool|Models_Exam_Question_Versions */
    public static function fetchRowByVersionID($version_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    /* @return bool|Models_Exam_Question_Versions */
    public static function fetchRowByExamsoftID($examsoft_id, $deleted_date = NULL) {
        $self = new self();
        $all = $self->fetchAll(array(
            array("key" => "examsoft_id", "value" => $examsoft_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
        usort($all, function ($a, $b) { return (int)$b->getID() - (int)$a->getID(); });
        return $all ? $all[0] : false;
    }

    /* @return ArrayObject|Models_Exam_Question_Versions[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecordsBySearchTerm($search_value = null, $limit = null, $offset = null, $sort_direction = null, $sort_column = null, $group_width = null, $question_type = null, $group_questions = null, $group_descriptors = null, $exclude_question_ids = null, $exam_id = null, $filters = array(), $folder_id = null, $search_sub_folders = false) {
        global $db, $ENTRADA_USER;

        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "question_id";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        if (isset($folder_id)) {
            $tmp_input = clean_input($folder_id, array("trim", "int"));
            if ($search_sub_folders === 1) {
                $folder_array = array($folder_id);
                $folder_children = Models_Exam_Question_Bank_Folders::getChildrenFolders($tmp_input, $folder_array);
            }
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "SELECT `versions`.`version_id`, `versions`.`question_id`, `versions`.`version_count`, `versions`.`questiontype_id`, `versions`.`question_text`, `versions`.`question_description`, `versions`.`question_rationale`, `versions`.`question_correct_text`, `versions`.`question_code`, `versions`.`grading_scheme`, `versions`.`organisation_id`, `versions`.`created_date`, `versions`.`created_by`, `versions`.`updated_date`, `versions`.`updated_by`, `versions`.`deleted_date`, `versions`.`examsoft_id`, `versions`.`examsoft_images_added`, `versions`.`examsoft_flagged`, `questions`.`folder_id`
                    FROM `exam_question_versions` AS versions
                        JOIN `exam_questions` AS `questions` 
                          ON `versions`.`question_id` = `questions`.`question_id` 
                        JOIN 
                        (
                            SELECT `v`.`question_id`, MAX(`v`.`version_id`) AS highest_version_id, `v`.`version_id`
                                FROM `exam_question_versions` as `v`";



        $query .= " WHERE `v`.`deleted_date` IS NULL
                    AND
                    (
                        `v`.`question_text` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `v`.`question_id` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `v`.`question_description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `v`.`question_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `v`.`question_rationale` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `v`.`question_id` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `v`.`examsoft_id` LIKE (". $db->qstr("%". $search_value ."%") .")
                )";

        //restricts to active org
        $query .= " AND `v`.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
        $query .= " GROUP BY `v`.`question_id`
        ) as `qv`
	ON `qv`.`highest_version_id` = `versions`.`version_id`";

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_question_authors` AS `authors`
                            ON `versions`.`question_id` = `authors`.`question_id`
                            AND `authors`.`author_type` = 'proxy_id'
                            AND `authors`.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_question_authors` AS d
                            ON `versions`.`question_id` = d.`question_id`
                            AND d.`author_type` = 'organisation_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["organisation"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `exam_question_authors` AS e
                            ON `versions`.`question_id` = e.`question_id`
                            AND e.`author_type` = 'course_id'
                            AND e.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `exam_question_objectives` AS g
                            ON `versions`.`question_id` = g.`question_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }

            if (array_key_exists("exam", $filters)) {
                $query .= " JOIN `exam_elements` AS ee
                            ON `versions`.`version_id` = `ee`.`element_id`
                            JOIN `exams` AS exams
                            ON `ee`.`exam_id` = `exams`.`exam_id`
                            AND `exams`.`exam_id` IN (". implode(",", array_keys($filters["exam"])) .")";
            }

        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                /*
                 * hides query that restricts non authors from viewing versions
                $query .= " JOIN `exam_question_authors` AS `authors`
                            ON `versions`.`question_id` = `authors`.`question_id`
                            AND
                            ("
                    .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["director"]).") OR" : "")
                    .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["pcoordinator"]).") OR" : "")
                    .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["ccoordinator"]).") OR" : "")
                    .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["pcoord_id"]).") OR" : "")."
                                (`authors`.`author_type` = 'proxy_id' AND `authors`.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (`authors`.`author_type` = 'organisation_id' AND `authors`.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                )
                */

            }
        }

        if (isset($folder_id) && $folder_id === 0 && isset($search_sub_folders) && $search_sub_folders === 1) {
            //no folder restrictions
        } else if (isset($folder_children) && is_array($folder_children)) {
            $folder_ids =  implode(",", $folder_children);
            $query .= " AND `questions`.`folder_id` IN (" . $folder_ids . ")";
        } else {
            $query .= " AND `questions`.`folder_id` = " . $folder_id;
        }

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND `authors`.`deleted_date` IS NULL";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("exam", $filters)) {
                $query .= " AND `ee`.`deleted_date` IS NULL";
            }

        } else if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            /*
             * hides query used to restrict to authors own questions
            $query .= " AND `authors`.`deleted_date` IS NULL";
            */
        }

        $query .= " ORDER BY `" . $sort_column . "` " . $sort_direction . "
                    LIMIT " . ($offset ? (int) $offset : 0) . ", " . (int) $limit;

        $results = $db->GetAll($query);

        return $results;
    }

    public static function countAllRecordsBySearchTerm($search_value = null, $limit = null, $offset = null, $sort_direction = null, $sort_column = null, $group_width = null, $question_type = null, $group_questions = null, $group_descriptors = null, $exclude_question_ids = null, $exam_id = null, $filters = array(), $folder_id = null, $search_sub_folders = false) {
        global $db, $ENTRADA_USER;

        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "question_id";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        if (isset($folder_id)) {
            $tmp_input = clean_input($folder_id, array("trim", "int"));
            if ($search_sub_folders === 1) {
                $folder_array = array($folder_id);
                $folder_children = Models_Exam_Question_Bank_Folders::getChildrenFolders($tmp_input, $folder_array);
            }
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT count(DISTINCT `versions`.`question_id`) as `total_questions`
                      FROM `exam_question_versions` AS `versions`
                    JOIN `exam_questions` AS `questions`
                    ON `versions`.`question_id` = `questions`.`question_id`";

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_question_authors` AS `authors`
                            ON `versions`.`question_id` = `authors`.`question_id`
                            AND `authors`.`author_type` = 'proxy_id'
                            AND `authors`.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_question_authors` AS d
                            ON `versions`.`question_id` = d.`question_id`
                            AND d.`author_type` = 'organisation_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["organisation"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `exam_question_authors` AS e
                            ON `versions`.`question_id` = e.`question_id`
                            AND e.`author_type` = 'course_id'
                            AND e.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `exam_question_objectives` AS g
                            ON `versions`.`question_id` = g.`question_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }

            if (array_key_exists("exam", $filters)) {
                $query .= " JOIN `exam_elements` AS ee
                            ON `versions`.`version_id` = `ee`.`element_id`
                            JOIN `exams` AS exams
                            ON `ee`.`exam_id` = `exams`.`exam_id`
                            AND `exams`.`exam_id` IN (". implode(",", array_keys($filters["exam"])) .")";
            }

        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                /*
                 * hides query that restricts non authors from viewing versions
                $query .= " JOIN `exam_question_authors` AS `authors`
                            ON `versions`.`question_id` = `authors`.`question_id`
                            AND
                            ("
                    .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["director"]).") OR" : "")
                    .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["pcoordinator"]).") OR" : "")
                    .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["ccoordinator"]).") OR" : "")
                    .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(`authors`.`author_type` = 'course_id' AND `authors`.`author_id` = ".$db->qstr($course_permissions["pcoord_id"]).") OR" : "")."
                                (`authors`.`author_type` = 'proxy_id' AND `authors`.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (`authors`.`author_type` = 'organisation_id' AND `authors`.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                )
                */

            }
        }

        $query .= " WHERE `versions`.`deleted_date` IS NULL
                    AND
                    (
                        `versions`.`question_text` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `versions`.`question_description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `versions`.`question_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `versions`.`question_rationale` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `versions`.`examsoft_id` LIKE (". $db->qstr("%". $search_value ."%") .")
                )";

        //restricts to active org
        $query .= " AND `versions`.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());

        if (isset($folder_id) && $folder_id === 0 && isset($search_sub_folders) && $search_sub_folders === 1) {
            //no folder restrictions
        } else if (isset($folder_children) && is_array($folder_children)) {
            $folder_ids =  implode(",", $folder_children);
            $query .= " AND `questions`.`folder_id` IN (" . $folder_ids . ")";
        } else {
            $query .= " AND `questions`.`folder_id` = " . $folder_id;
        }

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND `authors`.`deleted_date` IS NULL";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("exam", $filters)) {
                $query .= " AND `ee`.`deleted_date` IS NULL";
            }

        } else if ($ENTRADA_USER->getActiveGroup() != "medtech") {
            /*
             * hides query used to restrict to authors own versions
            $query .= " AND `authors`.`deleted_date` IS NULL";
            */
        }

        $results = $db->GetRow($query);

        if ($results) {
            return $results["total_questions"];
        }
        return 0;
    }

    public static function fetchAllRecordsBySearchTermQuestionType($search_value, $start, $limit, $sort_direction, $sort_column, $question_type = 1) {
        global $db;

        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "question_id";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        if ($search_value && $tmp_input = clean_input($search_value, array("trim", "striptags"))) {
            $where = "  WHERE `questions`.`question_text` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR b.`name` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `questions`.`question_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `questions`.`question_rationale` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR `questions`.`examsoft_id` LIKE (". $db->qstr("%". $search_value ."%") .")
                        AND `questions`.`questiontype_id` = " . $db->qstr($question_type);
        }

        $query = "  SELECT `questions`.*, b.`name`, COUNT(c.`question_id`) AS `answers`
                    FROM `exam_question_versions` AS `questions`
                    JOIN `exam_lu_questiontypes` AS b
                    ON `questions`.`questiontype_id` = b.`questiontype_id`
                    JOIN `exam_question_answers` AS c
                    ON `questions`.`question_id` = c.`question_id` AND c.`deleted_date` IS NULL
                    ". (isset($where) ? $where : "") ."
                    GROUP BY `questions`.`question_id`
                    ORDER BY `" . $sort_column . "` " . $sort_direction . "
                    " . ((int) $start != 0 ? "LIMIT " . (int) $start . ", " . (int) $limit : "LIMIT " . (int) $start . ", " . (int) 100);

        $results = $db->GetAll($query);

        return $results;
    }

    public function getQuestionType() {
        if (NULL === $this->question_type) {
            $this->question_type = Models_Exam_Lu_Questiontypes::fetchRowByID($this->questiontype_id);
        }

        return $this->question_type;
    }

    //todo check that this can output multiple correct
    public function getCorrectQuestionAnswer() {
        if (NULL === $this->question_correct_answer) {
            $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($this->version_id);
            $correct_answers_array = array();
            if (isset($answers) && is_array($answers)) {
                foreach ($answers as $answer) {
                    if (is_object($answer)) {
                        $correct = $answer->getCorrect();
                        if ($correct == 1) {
                            $alphas = range('A', 'Z');
                            $order = $answer->getOrder();
                            $letter = $alphas[$order - 1];
                            $correct_answers_array[] = $letter;
                        }
                    }
                }
            }
            $correct_answers = implode(", ", $correct_answers_array);
            $this->question_correct_answer = $correct_answers;
        }
        return $this->question_correct_answer;
    }

    /* @return ArrayObject|Models_Exam_Question_Answers[] */
    public function getQuestionAnswers() {
        if (NULL === $this->question_answers) {
            $this->question_answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($this->version_id);
        }
        return $this->question_answers;
    }

    /* @return ArrayObject|Models_Exam_Question_Match[] */
    public function getMatchStems() {
        if (NULL === $this->match_stems) {
            $this->match_stems = Models_Exam_Question_Match::fetchAllRecordsByVersionID($this->version_id);
        }
        return $this->match_stems;
    }

    /* @return ArrayObject|Models_Exam_Question_Objectives[] */
    public function getQuestionObjectives() {
        if (NULL === $this->question_objectives) {
            $this->question_objectives = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($this->question_id);
        }
        return $this->question_objectives;
    }

    /* @return ArrayObject|Models_Exams_Tag[] */
    public function getQuestionTags() {
        if (NULL === $this->question_tags) {
            $this->question_tags = Models_Exams_Tag::fetchAllRecordsByQuestionID($this->question_id);
        }
        return $this->question_tags;
    }

    /* @return bool|Models_Exam_Question_Bank_Folders */
    public function getParentFolder() {
        $question = $this->getQuestion();

        if (NULL === $this->parent_folder) {
            $this->parent_folder = $question->getParentFolder();
        }
        return $this->parent_folder;
    }

    public static function getLatestVersionByQuestionID($question_id) {
        global $db;
        $query = "  SELECT `version_id` FROM `exam_question_versions`
                    WHERE `question_id` = " . $db->qstr($question_id) ."
                    AND `deleted_date` IS NULL
                    ORDER BY `version_id` DESC
                    LIMIT 1";
        $results = $db->GetOne($query);
        return $results;
    }

    /* @return ArrayObject|Models_Exam_Question_Versions[] */
    public static function fetchAllByFolderID($folder_id, $deleted_date = NULL) {
        global $db;

        $query = "  SELECT `v`.*, `q`.`folder_id`
                    FROM `exam_question_versions` as `v`
                    JOIN `exam_questions` as `q`
                    ON `v`.`question_id` = `q`.`question_id`
                    WHERE `q`.`folder_id` = " . $db->qstr($folder_id) . "
                    AND v.`deleted_date` " . ($deleted_date ? " <= " .$db->qstr($deleted_date) : "IS NULL ") . "
                    ORDER BY v.`version_id` DESC";

        $results = $db->GetAll($query);
        $return_array = array();
        if ($results && is_array($results) && !empty($results)) {
            foreach ($results as $result) {
                $return_array[] = new self($result);
            }
        } else {
            $return_array = false;
        }

        return $return_array;
    }
    
    /* @return ArrayObject|Models_Exam_Question_Versions[] */
    public static function fetchAllExamsoftFlagged() {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "examsoft_flagged", "value" => 1)
        ));
    }

    public static function fetchQuestionsByProxyID($proxy_id = null) {
        global $db;
        $questions = false;

        $query = "  SELECT * FROM `exam_question_versions` AS `questions`
                    LEFT JOIN `exam_question_authors` AS b
                    ON `questions`.`question_id` = b.`question_id`
                    WHERE b.`author_type` = 'proxy_id'
                    AND b.`author_id` = '1'";

        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $questions[] = new self($result);
            }
        }
        return $questions;
    }

    public static function saveSubFolderSearchPreference($action) {
        global $PREFERENCES;

        if (!empty($action)) {
            $_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["sub_folder_search"] = $action;

            if (preferences_update("exams", $PREFERENCES)) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public static function saveFilterPreferences($filters = array()) {
        global $db, $PREFERENCES;

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
                        case "exam" :
                            $exam = Models_Exam_Exam::fetchRowByID($target);
                            if ($exam) {
                                $target_label = $exam->getTitle();
                            }
                            break;



                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
            preferences_update("exams", $PREFERENCES);
        }
    }
}