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
 * A Model for handling questions ids
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Questions extends Models_Base {
    protected   $question_id,
                $folder_id,
                $deleted_date;

    protected static $table_name = "exam_questions";
    protected static $primary_key = "question_id";
    protected static $default_sort_column = "question_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->question_id;
    }

    public function getQuestionID() {
        return $this->question_id;
    }

    public function getFolderID() {
        return $this->folder_id;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setQuestionID($id) {
        $this->question_id = $id;
    }

    public function setFolderID($id) {
        $this->folder_id = $id;
    }

    /* @return bool|Models_Exam_Question_Bank_Folders */
    public function getParentFolder() {
        if (NULL === $this->parent_folder) {
            $this->parent_folder = Models_Exam_Question_Bank_Folders::fetchRowByID($this->folder_id);
        }
        return $this->parent_folder;
    }

    /* @return bool|Models_Exam_Questions */
    public static function fetchRowByID($question_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "question_id", "value" => $question_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    /**
     * Returns a question that uses the given examsoft_id by checking the first
     * half of the examsoft_id/examsoft_version in the examsoft_id column of
     * the exam_question_versions table. Used by the exam import feature.
     * 
     * @global ADODB $db
     * @param int $examsoft_id
     * @return Models_Exam_Questions
     */
    public static function fetchRowByExamsoftID($examsoft_id) {
        global $db;
        $query = "
            SELECT `question_id`
            FROM `exam_question_versions` AS a
            WHERE a.`examsoft_id` LIKE ".$db->qstr($examsoft_id."/%");
        $question_id = $db->GetOne($query);
        return static::fetchRowByID($question_id);
    }

    /* @return ArrayObject|Models_Exam_Questions[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(
            array (
                array("key" => "question_id", "value" => 0, "method" => ">="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }
}