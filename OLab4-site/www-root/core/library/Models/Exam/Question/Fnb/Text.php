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

class Models_Exam_Question_Fnb_Text extends Models_Base {
    protected $fnb_text_id,
        $qanswer_id,
        $text,
        $updated_date,
        $updated_by,
        $deleted_date;

    protected static $table_name = "exam_question_fnb_text";
    protected static $primary_key = "fnb_text_id";
    protected static $default_sort_column = "qanswer_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->fnb_text_id;
    }

    public function getAnswerID() {
        return $this->qanswer_id;
    }

    public function getText() {
        return $this->text;
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

    public function setDeletedDate($time) {
        $this->deleted_date = $time;
    }

    /* @return bool|Models_Exam_Question_Fnb_Text */
    public static function fetchRowByID($fnb_text_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "fnb_text_id", "value" => $fnb_text_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Fnb_Text */
    public static function fetchRowByQuestionAnswerID($qanswer_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "qanswer_id", "value" => $qanswer_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Fnb_Text[] */
    public static function fetchAllByQuestionAnswerID($qanswer_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "qanswer_id", "value" => $qanswer_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Fnb_Text */
    public static function fetchRowByQuestionAnswerIDText($qanswer_id, $text, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "qanswer_id", "value" => $qanswer_id, "method" => "="),
            array("key" => "text", "value" => $text, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

}