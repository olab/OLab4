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
 * Model for representing file uploads for academic advisor meetings
 *
 * @author Organisation: Queens University
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_AcademicAdvisor_File extends Models_Base {

    protected $meeting_file_id;
    protected $meeting_id;
    protected $type;
    protected $size;
    protected $name;
    protected $title;
    protected $file_order;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;
    protected $deleted_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_meeting_files";
    protected static $primary_key = "meeting_file_id";
    protected static $default_sort_column = "meeting_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->meeting_file_id;
    }

    public function getMeetingFileID() {
        return $this->meeting_file_id;
    }

    public function setMeetingFileID($meeting_file_id) {
        $this->meeting_file_id = $meeting_file_id;

        return $this;
    }

    public function getMeetingID() {
        return $this->meeting_id;
    }

    public function setMeetingID($meeting_id) {
        $this->meeting_id = $meeting_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @param mixed $size
     */
    public function setSize($size) {
        $this->size = $size;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate() {
        return $this->created_date;
    }

    /**
     * @param mixed $created_date
     */
    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy() {
        return $this->created_by;
    }

    /**
     * @param mixed $created_by
     */
    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getFileOrder() {
        return $this->file_order;
    }

    /**
     * @param mixed $file_order
     */
    public function setFileOrder($file_order) {
        $this->file_order = $file_order;
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

        return $this;
    }

    public static function fetchRowByID($meeting_file_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "meeting_file_id", "method" => "=", "value" => $meeting_file_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "meeting_file_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    public static function fetchAllByMeetingID($meeting_id) {
        global $db;

        $query = "SELECT file.* FROM `cbl_meeting_files` AS file
                  INNER JOIN `cbl_academic_advisor_meetings` AS meeting
                  ON file.`meeting_id` = meeting.`meeting_id`
                  WHERE file.`meeting_id` = ?
                  AND file.`deleted_date` IS NULL
                  GROUP BY file.`meeting_file_id`
                  ORDER BY file.`name` ";

        $results = $db->GetAll($query, array($meeting_id));

        return $results;
    }

    public function getFileOrderByMeetingID($meeting_id) {
        global $db;

        $query = "SELECT MAX(`file_order`) as highest_order, COUNT(`meeting_file_id`) as total_files
                  FROM `cbl_meeting_files`
                  WHERE `meeting_id` = ?
                  HAVING COUNT(`meeting_file_id`) > 1";

        $results = $db->GetRow($query, array($meeting_id));

        return $results;
    }

}