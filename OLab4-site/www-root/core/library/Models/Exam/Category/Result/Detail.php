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
 * A Model for handling Category Results Details
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Category_Result_Detail extends Models_Base {
    protected $detail_id, $proxy_id, $exam_progress_id, $post_id, $exam_id, $objective_id, $set_id, $score, $value, $possible_value, $updated_date, $deleted_date;

    protected static $table_name = "exam_category_result_detail";
    protected static $primary_key = "detail_id";
    protected static $default_sort_column = "detail_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->detail_id;
    }

    public function getDetailID() {
        return $this->detail_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getExamProgressID() {
        return $this->exam_progress_id;
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

    public function getScore() {
        return $this->score;
    }

    public function getValue() {
        return $this->value;
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

    public function setScore($score) {
        $this->score = $score;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    /* @return bool|Models_Exam_Category_Result_Detail */
    public static function fetchRowByProxyIdObjectiveIdProgressId($proxy_id, $objective_id, $exam_progress_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Category_Result_Detail[] */
    public static function fetchAllByProgressID($exam_progress_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Category_Result_Detail[] */
    public static function fetchAllByPostID($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Category_Result_Detail */
    public static function fetchRowByID($detail_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "detail_id", "value" => $detail_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Category_Result_Detail[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "detail_id", "value" => 0, "method" => ">=")),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
    }
}