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
 * A Model for handling question objectives
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Objectives extends Models_Base {
    protected $qobjective_id, $question_id, $objective_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "exam_question_objectives";
    protected static $primary_key = "qobjective_id";
    protected static $default_sort_column = "question_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->qobjective_id;
    }

    public function getQobjectiveID() {
        return $this->qobjective_id;
    }

    public function getQuestionID() {
        return $this->question_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
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

    public function setID($id) {
        $this->qobjective_id = $id;
    }

    /* @return bool|Models_Exam_Question_Objectives */
    public static function fetchRowByID($qobjective_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "qobjective_id", "value" => $qobjective_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Objectives */
    public static function fetchRowByQuestionIdObjectiveId($question_id, $objective_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "question_id", "value" => $question_id, "method" => "="),
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Objectives[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    /* @return ArrayObject|Models_Exam_Question_Objectives[] */
    public static function fetchAllRecordsByQuestionID($question_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "question_id", "value" => $question_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Objectives[] */
    public static function fetchAllRecordsByObjectiveID($objective_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByPostID($post_id, $deleted_date = NULL) {
        global $db;

        $output = array();
        $query = "SELECT f.* FROM `exam_posts` as a
                    LEFT JOIN `exams` as b
                        ON a.`exam_id` = b.`exam_id`
                    LEFT JOIN `exam_elements` as c
                        ON b.`exam_id` = c.`exam_id`
                    LEFT JOIN `exam_question_versions` as d
                        ON c.`element_id` = d.`version_id`
                    LEFT JOIN `exam_questions` as e
                        ON d.`question_id` = e.`question_id`
                    LEFT JOIN `exam_question_objectives` as f
                        ON e.`question_id` = f.`question_id`
                    WHERE a.`post_id` = ?
                    AND c.`element_type` = 'question'";
        $query .= " AND f.`deleted_date` " . ($deleted_date ? "<= ". $deleted_date : "IS NULL");
        $query .= " AND c.`deleted_date` " . ($deleted_date ? "<= ". $deleted_date : "IS NULL");
        $query .= " GROUP BY f.`objective_id`";

        $results = $db->GetAll($query, array($post_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    public static function fetchAllByExamID($exam_id, $deleted_date = NULL) {
        global $db;

        $output = array();
        $query = "SELECT f.* FROM `exam_posts` as a
                    LEFT JOIN `exams` as b
                        ON a.`exam_id` = b.`exam_id`
                    LEFT JOIN `exam_elements` as c
                        ON b.`exam_id` = c.`exam_id`
                    LEFT JOIN `exam_question_versions` as d
                        ON c.`element_id` = d.`version_id`
                    LEFT JOIN `exam_questions` as e
                        ON d.`question_id` = e.`question_id`
                    LEFT JOIN `exam_question_objectives` as f
                        ON e.`question_id` = f.`question_id`
                    WHERE b.`exam_id` = ?
                    AND c.`element_type` = 'question'";
        $query .= " AND f.`deleted_date` " . ($deleted_date ? "<= ". $deleted_date : "IS NULL");
        $query .= " AND c.`deleted_date` " . ($deleted_date ? "<= ". $deleted_date : "IS NULL");
        $query .= " GROUP BY f.`objective_id`";

        $results = $db->GetAll($query, array($exam_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }
}