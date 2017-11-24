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
 * A model for handling response to an exam post progress responses
 * all answer responses are stored here
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Progress_Response_Answers extends Models_Base {
    protected
        $epr_answer_id,
        $epr_id, //links to Models_Exam_Progress_Responses
        $eqa_id,
        $eqm_id,
        $response_value,
        $response_element_order,
        $response_element_letter,
        $created_date,
        $created_by,
        $updated_date,
        $updated_by,
        $deleted_date,
        $progress,
        $progress_response,
        $exam,
        $post;

    protected static $table_name = "exam_progress_response_answers";
    protected static $primary_key = "epr_answer_id";
    protected static $default_sort_column = "epr_answer_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->epr_answer_id;
    }

    public function getExamProgressResponseID() {
        return $this->epr_id;
    }

    public function getAnswerElementID() {
        return $this->eqa_id;
    }

    public function getMatchID() {
        return $this->eqm_id;
    }

    public function getResponseValue() {
        return $this->response_value;
    }

    public function getResponseElementOrder() {
        return $this->response_element_order;
    }

    public function getResponseElementLetter() {
        return $this->response_element_letter;
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

    public function setResponseValue($response) {
        $this->response_value = $response;
    }

    public function setAnswerElementID($value) {
        $this->eqa_id = $value;
    }

    public function setResponseElementOrder($value) {
        $this->response_element_order = $value;
    }

    public function setResponseElementLetter($value) {
        $this->response_element_letter = $value;
    }

    public function setUpdateDate($time) {
        $this->updated_date = $time;
    }

    public function setUpdatedBy($user) {
        $this->updated_by = $user;
    }

    public function setMatchID($id) {
        $this->match_id = $id;
    }

    public function setAnswerID($id) {
        $this->eqa_id = $id;
    }

    public function getProgressResponse() {
        if (NULL === $this->progress_response) {
            $this->progress_response = Models_Exam_Progress_Responses::fetchRowByID($this->epr_id);
        }

        return $this->progress_response;
    }

    public function getProgress() {
        if (NULL === $this->progress) {
            $this->progress = Models_Exam_Progress::fetchRowByID($this->getProgressResponse()->getID());
        }

        return $this->progress;
    }

    public function getExam() {
        if (NULL === $this->exam) {
            $this->exam = Models_Exam_Exam::fetchRowByID($this->getProgress()->getExam());
        }

        return $this->exam;
    }

    public function getPost() {
        if (NULL === $this->post) {
            $this->post = Models_Exam_Post::fetchRowByID($this->getProgress()->getExamPost());
        }

        return $this->post;
    }

    /* @return bool|Models_Exam_Progress_Response_Answers */
    public static function fetchRowByID($epr_answer_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "epr_answer_id", "value" => $epr_answer_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Progress_Response_Answers */
    public static function fetchRowByAnswerElement($epr_id, $eqa_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "epr_id", "value" => $epr_id, "method" => "="),
            array("key" => "eqa_id", "value" => $eqa_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Progress_Response_Answers */
    public static function fetchRowByResponseIdMatchId($epr_id, $eqm_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "epr_id", "value" => $epr_id, "method" => "="),
            array("key" => "eqm_id", "value" => $eqm_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Response_Answers[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "epr_answer_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Response_Answers[] */
    public static function fetchAllByExamProgressResponseID($epr_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "epr_id", "value" => $epr_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Response_Answers[] */
    public static function fetchAllByExamProgressResponseIDTrue($epr_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "epr_id", "value" => $epr_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}