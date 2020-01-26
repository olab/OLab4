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
 * A Model for handling Category Results
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Category_Result extends Models_Base {
    protected $result_id, $post_id, $exam_id, $objective_id, $set_id, $average, $min, $max, $total_exam_takers, $total_correct, $percent_correct, $total_incorrect, $percent_incorrect, $total_questions, $total_responses, $possible_value, $updated_date, $deleted_date;

    protected static $table_name           = "exam_category_result";
    protected static $primary_key          = "result_id";
    protected static $default_sort_column  = "result_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->result_id;
    }

    public function getResultID() {
        return $this->result_id;
    }

    public function getPostID() {
        return $this->post_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getSetID() {
        return $this->set_id;
    }

    public function getAverage() {
        return $this->average;
    }

    public function getMin() {
        return $this->min;
    }

    public function getMax() {
        return $this->max;
    }

    public function getTotalExamTakers() {
        return $this->total_exam_takers;
    }

    public function getTotalCorrect() {
        return $this->total_correct;
    }

    public function getPercentCorrect() {
        return $this->percent_correct;
    }

    public function getTotalIncorrect() {
        return $this->total_incorrect;
    }

    public function getPercentIncorrect() {
        return $this->percent_incorrect;
    }

    public function getTotalQuestions() {
        return $this->total_questions;
    }

    public function getTotalResponses() {
        return $this->total_responses;
    }

    public function getPossibleValue() {
        return $this->possible_value;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setAverage($average) {
        $this->average = $average;
    }

    public function setMax($max) {
        $this->max = $max;
    }

    public function setMin($min) {
        $this->min = $min;
    }

    public function setTotalCorrect($total_correct) {
        $this->total_correct = $total_correct;
    }

    public function setTotalIncorrect($total_incorrect) {
        $this->total_incorrect = $total_incorrect;
    }

    public function setTotalQuestions($total_questions) {
        $this->total_questions = $total_questions;
    }

    public function setPossibleValue($possible_value) {
        $this->possible_value = $possible_value;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    /* @return ArrayObject|Models_Exam_Category_Result[] */
    public static function fetchAllByPostID($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Category_Result */
    public static function fetchRowByID($result_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "result_id", "value" => $result_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Category_Result */
    public static function fetchRowByObjectiveIdPostId($objective_id, $post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Category_Result[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "result_id", "value" => 0, "method" => ">=")),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
    }
}