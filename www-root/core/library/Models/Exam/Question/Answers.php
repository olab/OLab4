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
 * A model to handle the answers for questions
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Answers extends Models_Base {
    protected $qanswer_id, $question_id, $version_id, $answer_text, $answer_rationale, $correct, $weight, $order, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "exam_question_answers";
    protected static $primary_key = "qanswer_id";
    protected static $default_sort_column = "order";

    protected $fnb_correct_text;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->qanswer_id;
    }

    public function getQuestionID() {
        return $this->question_id;
    }

    public function getVersionID() {
        return $this->version_id;
    }

    public function getAnswerText() {
        return $this->answer_text;
    }

    public function getRationale() {
        return $this->answer_rationale;
    }

    public function getCorrect() {
        return (int)$this->correct;
    }
    
    /* Gets whether this answer is correct, taking into account the exam_adjustments table for a specific exam element and exam. */
    public function getAdjustedCorrect($exam_element_id, $exam_id) {
        $correct_adj = Models_Exam_Adjustment::fetchRowByElementIDExamIDTypeValue($exam_element_id, $exam_id, "correct", $this->getID());
        $incorrect_adj = Models_Exam_Adjustment::fetchRowByElementIDExamIDTypeValue($exam_element_id, $exam_id, "incorrect", $this->getID());
        if ($correct_adj) {
            return 1;
        } else if ($incorrect_adj) {
            return 0;
        } else {
            return $this->getCorrect();
        }
    }

    public function getWeight() {
        return $this->weight;
    }

    public function getOrder() {
        return $this->order;
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

    public function setRationale($answer_rationale) {
        $this->answer_rationale = $answer_rationale;
    }

    public function setWeight($weight) {
        $this->weight = $weight;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function getFnbText() {
        if (NULL === $this->fnb_correct_text) {
            $this->fnb_correct_text = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($this->getID());
        }
        return $this->fnb_correct_text;
    }

    /* @return bool|Models_Exam_Question_Answers */
    public static function fetchRowByID($qanswer_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "qanswer_id", "value" => $qanswer_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Answers[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Answers[] */
    public static function fetchAllRecordsByQuestionID($question_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "question_id", "value" => $question_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Answers[] */
    public static function fetchAllRecordsByVersionID($version_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Answers[] */
    public static function fetchAllCorrectByVersionID($version_id, $correct = 1, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "correct", "value" => $correct, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Answers[] */
    public static function fetchAllRecordsByQuestionIDResponseID($question_id, $eq_revision_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "question_id", "value" => $question_id, "method" => "="),
            array("key" => "eq_revision_id", "value" => $eq_revision_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Answers */
    public static function fetchRowByVersionIDOrder($version_id, $order, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "order", "value" => $order, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}