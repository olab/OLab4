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
 * A model to handle the match stems for match questions
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Match extends Models_Base
{
    protected $match_id, $version_id, $match_text, $order, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "exam_question_match";
    protected static $primary_key = "match_id";
    protected static $default_sort_column = "order";

    public function getID() {
        return $this->match_id;
    }

    public function getVersionID() {
        return $this->version_id;
    }

    public function getMatchText() {
        return $this->match_text;
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
        $this->match_id = $id;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function setText($text) {
        $this->match_text = $text;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }


    /* @return bool|Models_Exam_Question_Match */
    public static function fetchRowByID($match_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "match_id", "value" => $match_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Question_Match */
    public static function fetchRowByVersionIdOrder($version_id, $order, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "order", "value" => $order, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Match[] */
    public static function fetchAllRecordsByVersionID($version_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "version_id", "value" => $version_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Match[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}