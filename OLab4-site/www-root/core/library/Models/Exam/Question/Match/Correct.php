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
 * A model to handle the match correct for match questions
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Match_Correct extends Models_Base
{
    protected $eqm_correct_id, $match_id, $qanswer_id, $correct, $updated_date, $updated_by, $deleted_date;

    protected $match_answer;
    protected static $table_name = "exam_question_match_correct";
    protected static $primary_key = "eqm_correct_id";
    protected static $default_sort_column = "eqm_correct_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->eqm_correct_id;
    }

    public function getMatchId() {
        return $this->match_id;
    }

    public function getQAnswerID() {
        return $this->qanswer_id;
    }

    public function getCorrect() {
        return $this->correct;
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

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public function setID($id) {
        $this->qanswer_id = $id;
    }

    public function setCorrect($correct) {
        $this->correct = $correct;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    /* @return bool|Models_Exam_Question_Answers */
    public function getAnswer() {
        if (NULL === $this->match_answer){
            $this->match_answer = Models_Exam_Question_Answers::fetchRowByID($this->qanswer_id);
        }
        return $this->match_answer;
    }

    /* @return bool|Models_Exam_Question_Match_Correct */
    public static function fetchRowByID($eqm_correct_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eqm_correct_id", "value" => $eqm_correct_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Match_Correct */
    public static function fetchRowByMatchID($match_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "match_id", "value" => $match_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Match_Correct */
    public static function fetchRowByQAnswerID($qanswer_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "qanswer_id", "value" => $qanswer_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Match_Correct[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}