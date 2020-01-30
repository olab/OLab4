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
 * A model to handling the questions in a grouped question or set
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Group_Question extends Models_Base {
    protected $egquestion_id, $group_id, $question_id, $version_id, $order, $updated_by, $updated_date, $deleted_date;

    protected static $table_name = "exam_group_questions";
    protected static $primary_key = "egquestion_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->egquestion_id;
    }

    public function getGroupID() {
        return $this->group_id;
    }

    public function getQuestionID() {
        return $this->question_id;
    }
    
    public function getVersionID() {
        return $this->version_id;
    }

    public function getAnswerText() {
        return $this->version_id;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setID($id) {
        $this->egquestion_id = $id;
    }

    public function setGroupId($group_id) {
        $this->group_id = $group_id;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function setVersionId($version_id) {
        $this->version_id = $version_id;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    /* @return bool|Models_Exam_Question_Versions */
    public function getQuestionVersion(){
        return Models_Exam_Question_Versions::fetchRowByVersionID($this->version_id);
    }

    /* @return bool|Models_Exam_Group */
    public function getGroup(){
        return Models_Exam_Group::fetchRowByID($this->group_id);
    }

    /* @return bool|Models_Exam_Group_Question */
    public static function fetchRowByID($egquestion_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "egquestion_id", "value" => $egquestion_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Group_Question */
    public static function fetchRowByQuestionIDGroupID($question_id, $group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "question_id", "value" => $question_id, "method" => "="),
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Group_Question */
    public static function fetchRowByVersionIDGroupID($version_id, $group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Group_Question */
    public static function fetchRowByVersionID($version_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Group_Question[] */
    public static function fetchAllByVersionID($version_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Group_Question[] */
    public static function fetchAllByAuthorIDGroupID($author_id, $group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "author_id", "value" => $author_id, "method" => "="),
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Group_Question[] */
    public static function fetchAllByGroupID($group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Group_Question[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }
    
    public static function fetchNextOrder($group_id) {
        global $db;
        $query = "SELECT MAX(`order`) + 1 AS `next_order` FROM `exam_group_questions` WHERE `group_id` = ?";
        $result = $db->GetOne($query, array($group_id));
        return $result ? $result : "0";
    }

    public function delete() {
        $this->deleted_date = time();
        $this->updated_date = time();

        return $this->update();
    }
}