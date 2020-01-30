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
 * Model to handle courses' files
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Course_Files extends Models_Base {
    protected $id, $course_id, $required, $timeframe, $file_category, $file_type, $file_size, $file_name, $file_title, $file_notes, $valid_from, $valid_until, $access_method, $accesses, $updated_date, $updated_by;

    protected $table_name = "course_files";
    protected $primary_key = "id";
    protected $default_sort_column = "course_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }
    
    public function getCourseID() {
        return $this->course_id;
    }

    public function getRequired() {
        return $this->required;
    }

    public function getTimeframe() {
        return $this->timeframe;
    }

    public function getFileCategory() {
        return $this->file_category;
    }

    public function getFileType() {
        return $this->file_type;
    }

    public function getFileSize() {
        return $this->file_size;
    }

    public function getFileName() {
        return $this->file_name;
    }

    public function getFileTitle() {
        return $this->file_title;
    }

    public function getFileNotes() {
        return $this->file_notes;
    }

    public function getValidFrom() {
        return $this->valid_from;
    }

    public function getValidUntil() {
        return $this->valid_until;
    }

    public function getAccessMethod() {
        return $this->access_method;
    }

    public function getAccesses() {
        return $this->accesses;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "id", "value" => 0, "method" => ">=")));
    }
}