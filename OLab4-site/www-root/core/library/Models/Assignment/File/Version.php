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
 * A model for handling specific versions of uploaded files for gradebook assignments.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Assignment_File_Version extends Models_Base {
    protected $afversion_id, $afile_id, $assignment_id, $proxy_id, $file_mimetype, $file_version, $file_filename, $file_filesize, $file_active, $updated_date, $updated_by;

    protected static $table_name = "assignment_file_versions";
    protected static $primary_key = "afversion_id";
    protected static $default_sort_column = "updated_date";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afversion_id;
    }

    public function getAfversionID() {
        return $this->afversion_id;
    }

    public function getAfileID() {
        return $this->afile_id;
    }

    public function getAssignmentID() {
        return $this->assignment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getFileMimetype() {
        return $this->file_mimetype;
    }

    public function getFileVersion() {
        return $this->file_version;
    }

    public function getFileFilename() {
        return $this->file_filename;
    }

    public function getFileFilesize() {
        return $this->file_filesize;
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

    public function fetchMostRecentAssignmentFile() {
        return $this->fetchRow(array(
            array("key" => "proxy_id", "value" => $this->proxy_id, "method" => "="),
            array("key" => "assignment_id", "value" => $this->assignment_id, "method" => "=")
        ), "=", "AND", "file_version", "DESC");
    }

    /**
     * Fetches a list of the most recent version of all files a student has uploaded for a given assignment_id
     * @param  array  $proxy_ids Optional. If an array of proxy_ids is provided, use that instead of the object proxy ID.
     * @return array
     */
    public function fetchAllMostRecentAssignmentFiles($proxy_ids = array()) {
        global $db;

        $query = "SELECT * FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    INNER JOIN (
                        SELECT afile_id, MAX(file_version) AS file_version
                        FROM `".DATABASE_NAME."`.`".static::$table_name."`
                        WHERE file_active = 1
                        GROUP BY afile_id
                    ) AS max_file_version USING (afile_id, file_version)
                    INNER JOIN `".DATABASE_NAME."`.`assignment_files` b
                    ON a.afile_id = b.afile_id
                    WHERE b.file_type = 'submission' ";

        $query .= $proxy_ids ? "AND a.proxy_id IN (".implode(",", array_map(array($db, 'qstr'), $proxy_ids)).")" : "AND a.proxy_id = ".$db->qstr($this->proxy_id);

        $query .= " AND a.assignment_id = ?
                    ORDER BY a.updated_date DESC";

        $results = $db->getAll($query, array($this->assignment_id));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function fetchRowByIDWithFileType() {
        global $db;

        $query = "SELECT * FROM `".static::$table_name."` a
                    LEFT JOIN `filetypes` b
                    ON a.file_mimetype = b.mime
                    WHERE a.afversion_id = ?
                    AND a.file_active = ?";

        $results = $db->getRow($query, array($this->afversion_id, $this->file_active ? $this->file_active : 1));

        if ($results) {
            return $results;
        }

        return false;
    }

    public static function fetchMostRecentByAFileID($afile_id, $file_active = true) {
        $self = new self();
        $output = false;
        $results = $self->fetchAll(array(
            array("key" => "afile_id", "value" => $afile_id, "method" => "="),
            array("key" => "file_active", "value" => $file_active, "method" => "=")
        ), "=", "AND", "file_version", "DESC");
        if ($results) {
            $output = $results[0];
        }
        return $output;
    }

    public static function fetchRowByID($afversion_id, $file_active = true) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afversion_id", "value" => $afversion_id, "method" => "="),
            array("key" => "file_active", "value" => $file_active, "method" => "=")
        ));
    }

    public static function fetchAllRecords($file_active = true) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "file_active", "value" => $file_active, "method" => "=")));
    }

    public static function fetchOneByAssignmentIDFileIDFileVersion($assignment_id, $file_id, $file_version, $file_active = true) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assignment_id", "value" => $assignment_id, "method" => "="),
            array("key" => "afile_id", "value" => $file_id, "method" => "="),
            array("key" => "file_version", "value" => $file_version, "method" => "="),
            array("key" => "file_active", "value" => $file_active, "method" => "=")
        ));
    }

    public static function getAllUserAssignmentFilesByAssignmentIDProxyID($assignment_id, $proxy_id, $file_type = "submission", $file_version_active = true, $file_active = true) {
        global $db;

        $query = "SELECT a.*, c.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `uploader`, b.`username` AS `uploader_username`, b.`number`
                  FROM `assignment_file_versions` AS a
                  JOIN `assignment_files` AS c
                  ON a.`afile_id`=c.`afile_id`
                  LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                  ON a.`proxy_id`=b.`id`
                  WHERE a.`assignment_id`=?
                  AND a.`proxy_id`=?
                  AND a.`file_active` = ?
                  AND c.`file_active` = ?
                  AND c.`file_type`= ?
                  AND a.`file_version`=
                      (SELECT MAX(`file_version`)
                       FROM `assignment_file_versions`
                       WHERE `file_active`= ?
                       AND `afile_id`=a.`afile_id`)";

        $assignment_files = $db->GetAll($query, array($assignment_id, $proxy_id, $file_version_active, $file_active, $file_type, $file_version_active));

        if ($assignment_files) {
            return $assignment_files;
        }

        return false;
    }

    public static function getAllUserAssignmentFilesVersionsByAssignmentIDFileID($assignment_id, $file_id, $file_type = "submission", $file_version_active = true) {
        global $db;

        $query = "SELECT a.*,  CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `uploader`, b.`number`, b.`username` AS `uploader_username`
                    FROM `assignment_file_versions` AS a
                    JOIN `assignment_files` AS c
                    ON a.`afile_id` = c.`afile_id`
                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`afile_id` = ?
                    AND a.`assignment_id` = ?
                    AND a.`file_active` = ?
                    AND c.`file_type` = ?
                    ORDER BY a.`file_version` DESC";

        $assignment_files = $db->GetAll($query, array($file_id, $assignment_id, $file_version_active, $file_type));

        if ($assignment_files) {
            return $assignment_files;
        }

        return false;
    }

    public static function getAllTeacherAssignmentFilesVersionsByAssignmentIDFileID($assignment_id, $file_id, $file_type = "response", $file_version_active = true) {
        global $db;

        $query = "SELECT a.*,  CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `uploader`, b.`number`, b.`username` AS `uploader_username`
                    FROM `assignment_file_versions` AS a
                    JOIN `assignment_files` AS c
                    ON a.`afile_id` = c.`afile_id`
                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE c.`parent_id` = ?
                    AND a.`assignment_id` = ?
                    AND a.`file_active` = ?
                    AND c.`file_type` = ?
                    ORDER BY a.`file_version` DESC";

        $assignment_files = $db->GetAll($query, array($file_id, $assignment_id, $file_version_active, $file_type));

        if ($assignment_files) {
            return $assignment_files;
        }

        return false;
    }
}

