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
 * 
 *
 * @author Organisation: 
 * @author Developer:  <>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Exam_Exam_File extends Models_Base {
    protected $file_id, $exam_id, $file_name, $file_type, $file_title, $file_size, $updated_date, $updated_by, $deleted_date;

    protected static $table_name           = "exam_attached_files";
    protected static $primary_key          = "file_id";
    protected static $default_sort_column  = "updated_date";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->file_id;
    }

    public function getFileId() {
        return $this->file_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getFileName() {
        return $this->file_name;
    }

    public function getFileType() {
        return $this->file_type;
    }

    public function getFileTitle() {
        return $this->file_title;
    }

    public function getFileSize() {
        return $this->file_size;
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

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    /* @return bool|Models_Exam_Exam_File */
    public static function fetchRowByID($file_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "file_id", "value" => $file_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_File[] */
    public static function fetchAllByExamId($exam_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ), "=", "AND", $self::$default_sort_column, "DESC");
    }

    /* @return ArrayObject|Models_Exam_Exam_File[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}