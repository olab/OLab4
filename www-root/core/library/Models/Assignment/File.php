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
 * A model for handling files uploaded for gradebook assignments.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Assignment_File extends Models_Base {
    protected $afile_id, $assignment_id, $parent_id, $proxy_id, $file_type, $file_title, $file_description, $file_active, $updated_date, $updated_by;

    protected static $table_name = "assignment_files";
    protected static $primary_key = "afile_id";
    protected static $default_sort_column = "file_title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afile_id;
    }

    public function getAfileID() {
        return $this->afile_id;
    }

    public function getAssignmentID() {
        return $this->assignment_id;
    }

    public function getParentID() {
        return $this->parent_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getFileType() {
        return $this->file_type;
    }

    public function getFileTitle() {
        return $this->file_title;
    }

    public function getFileDescription() {
        return $this->file_description;
    }

    public function getFileActive() {
        return $this->file_active;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($afile_id, $file_active = true) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afile_id", "value" => $afile_id, "method" => "="),
            array("key" => "file_active", "value" => $file_active, "method" => "=")
        ));
    }

    public static function fetchRowByAssignmentIDProxyID($assignment_id, $proxy_id, $file_active = true) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assignment_id", "value" => $assignment_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "file_active", "value" => $file_active, "method" => "=")
        ));
    }

    public static function fetchAllByAssignmentIDProxyID($assignment_id, $proxy_id, $file_active = true) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "assignment_id", "value" => $assignment_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "file_active", "value" => $file_active, "method" => "=")
        ));
    }

    public static function fetchAllRecords($file_active = true) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "file_active", "value" => $file_active, "method" => "=")));
    }
    
    public static function getRowFileAssignmentByAssignmentIDProxyID($assignment_id, $proxy_id, $file_active = true, $assignment_active = true) {
        global $db;

        $query = "SELECT a.*, b.`course_id`, b.`assignment_title`, c.`number`
                    FROM `assignment_files` AS a
                    JOIN `assignments` AS b
                    ON a.`assignment_id` = b.`assignment_id`
                    JOIN `".AUTH_DATABASE."`.`user_data` AS c
                    ON a.`proxy_id` = c.`id`
                    WHERE `file_active` = ?
                    AND b.`assignment_active` = ?
                    AND a.`assignment_id` = ?
                    AND a.`proxy_id` = ? ";

        $assignment = $db->GetRow($query, array($file_active, $assignment_active, $assignment_id, $proxy_id));

        if ($assignment) {
            return $assignment;
        }

        return false;
    }

    public static function fetchAllByAssignmentIDProxyIDFileType($assignment_id, $proxy_id, $file_type = "submission", $file_active = true) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "assignment_id", "value" => $assignment_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "file_active", "value" => $file_active, "method" => "="),
            array("key" => "file_type", "value" => $file_type, "method" => "=")
        ));
    }
}