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
 * Model for handling the Academic Advisor meetings
 *
 * @author Organisation: Queens University
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_AcademicAdvisor_Meeting extends Models_Base {

    protected $meeting_id;
    protected $meeting_date;
    protected $meeting_member_id;
    protected $comment;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;
    protected $deleted_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_academic_advisor_meetings";
    protected static $primary_key = "meeting_id";
    protected static $default_sort_column = "meeting_date";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->meeting_id;
    }

    public function getMeetingID() {
        return $this->meeting_id;
    }

    public function setMeetingID($meeting_id) {
        $this->meeting_id = $meeting_id;
        return $this;
    }

    public function getMeetingDate() {
        return $this->meeting_date;
    }

    public function setMeetingDate($meeting_date) {
        $this->meeting_date = $meeting_date;
        return $this;
    }

    public function getMeetingMemberID() {
        return $this->meeting_member_id;
    }

    public function setMeetingMemberID($meeting_member_id) {
        $this->meeting_member_id = $meeting_member_id;
        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
        return $this;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;
    }

    public function getComment() {
        return $this->comment;
    }

    public function setComment($comment) {
        $this->comment = $comment;
    }

    public static function fetchRowByID($meeting_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "meeting_id", "method" => "=", "value" => $meeting_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "meeting_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }
        return $this->update();
    }

    public function fetchAllByMemberIDCreatedBy($member_id, $created_by_user) {
        global $db;
        $query = "SELECT * FROM `cbl_academic_advisor_meetings`
                  WHERE `meeting_member_id` = ?
                  AND `deleted_date` IS NULL
                  AND `created_by` = ?
                  ORDER BY `meeting_date` DESC";
        $results = $db->GetAll($query, array($member_id, $created_by_user));
        return $results;
    }

    public function fetchAllByMemberID($member_id) {
        global $db;
        $query = "SELECT * FROM `cbl_academic_advisor_meetings`
                  WHERE `meeting_member_id` = ?
                  AND `deleted_date` IS NULL
                  ORDER BY `meeting_date` DESC";

        $results = $db->GetAll($query, array($member_id));

        return $results;
    }

    public function fetchRowByMeetingIDCreatedBy($meeting_id, $created_by_user) {
        global $db;
        $query = "SELECT * FROM `cbl_academic_advisor_meetings`
                  WHERE `meeting_id` = ?
                  AND `created_by` = ?
                  AND `deleted_date` IS NULL
                  ORDER BY `meeting_date` DESC";

        $results = $db->GetRow($query, array($meeting_id, $created_by_user));

        return $results;
    }

}