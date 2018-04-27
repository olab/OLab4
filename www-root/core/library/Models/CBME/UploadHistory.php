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
 * A model for handling history and  storage of CBME file uploads.
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_CBME_UploadHistory extends Models_Base {
    protected $file_name, $file_type, $course_id, $upload_type, $created_date, $created_by;

    protected static $table_name = "cbl_cbme_upload_history";
    protected static $default_sort_column = "file_name";

    public function getFileName() {
        return $this->file_name;
    }

    public function getFileType() {
        return $this->file_type;
    }

    public function getCourseID () {
        return $this->course_id;
    }

    public function getUploadType() {
        return $this->upload_type;
    }

    public function getCreatedDate () {
        return $this->created_date;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    /**
     * Store a file in the CBME_STORAGE directory and log in the upload history table.
     *
     * @param $original_file
     * @param $file_name
     * @param $file_type
     * @param $proxy_id
     * @param $course_id
     * @param $upload_type
     *
     * @return bool
     */
    public function storeFileUploadHistory($original_file, $file_name, $file_type, $proxy_id, $course_id, $upload_type) {
        $timestamp = time();
        $base_storage_path = CBME_UPLOAD_STORAGE_PATH;
        $upload_key = "$proxy_id-$timestamp";

        $new_file = "$base_storage_path/$upload_key";

        if (!@move_uploaded_file($original_file, $new_file)) {
            application_log("error", "Unable to copy CBME file upload to upload directory [$original_file] to [$new_file]");
            return false;
        }

        global $db;
        @chmod($new_file, 0644);

        $upload_history = new self(array(
            "file_name"     => $file_name,
            "file_type"     => $file_type,
            "course_id"     => $course_id,
            "upload_type"   => $upload_type ? $upload_type : "",
            "created_by"    => $proxy_id,
            "created_date"  => $timestamp
        ));
        if (!$upload_history->insert()) {
            application_log("error", "Unable to insert history of CBME file upload [$new_file], DB said: " . $db->ErrorMsg());
        }

        return true;
    }

}