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

class Models_Exam_Question_Bank_Folder_Organisations extends Models_Base {
    protected $folder_org_id, $folder_id, $organisation_id, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "exam_question_bank_folder_organisations";
    protected static $primary_key = "folder_org_id";
    protected static $default_sort_column = "folder_org_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->folder_org_id;
    }

    public function getFolderOrgId() {
        return $this->folder_org_id;
    }

    public function getFolderID() {
        return $this->folder_id;
    }

    public function getOrganisationId() {
        return $this->organisation_id;
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

    /* @return bool|Models_Exam_Question_Bank_Folder_Organisations */
    public static function fetchRowByID($folder_org_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "folder_org_id", "value" => $folder_org_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Bank_Folder_Organisations[] */
    public static function fetchAllByFolderID($folder_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "folder_id", "value" => $folder_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}