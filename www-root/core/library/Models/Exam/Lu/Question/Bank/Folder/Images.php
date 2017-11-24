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
 * @copyright Copyright 2015 . All Rights Reserved.
 */

class Models_Exam_Lu_Question_Bank_Folder_Images extends Models_Base {
    protected $image_id, $file_name, $color, $order, $deleted_date;

    protected static $table_name = "exam_lu_question_bank_folder_images";
    protected static $primary_key = "image_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->image_id;
    }

    public function getImageID() {
        return $this->image_id;
    }

    public function getFileName() {
        return $this->file_name;
    }

    public function getColor() {
        return $this->color;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }


    /* @return bool|Models_Exam_Lu_Question_Bank_Folder_Images */
    public static function fetchRowByID($image_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "image_id", "value" => $image_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Lu_Question_Bank_Folder_Images[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}