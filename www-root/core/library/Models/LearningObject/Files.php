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
 * A model to handle learning object files.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_LearningObject_Files extends Models_Base {

    protected $lo_file_id;
    protected $filename;
//    protected $filename_hashed;
    protected $filesize;
    protected $mime_type;
    protected $description;
    protected $proxy_id;
    protected $public = 0;
    protected $updated_date;
    protected $updated_by;
    protected $active = 1;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "learning_object_files";
    protected static $primary_key = "lo_file_id";
    protected static $default_sort_column = "updated_date";
    protected static $default_sort_order = "ASC";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getLoFileID() {
        return $this->lo_file_id;
    }

    public function getFilename() {
        return $this->filename;
    }

//    public function getFilenameHashed() {
//        return $this->filename_hashed;
//    }

    public function getFilesize() {
        return $this->filesize;
    }

    public function getMimeType() {
        return $this->mime_type;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }
    
    public function getPublic() {
        return $this->public;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getActive() {
        return $this->active;
    }

    public static function fetchRowByID($lo_file_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "lo_file_id", "value" => $lo_file_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecordsByProxyID($proxy_id, $mime_type = NULL, $active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "proxy_id",
                "value"     => $proxy_id,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "active",
                "value"     => $active,
                "method"    => "="
            )
        );

        if (!is_null($mime_type)) {
            $constraints[] = array(
                "mode"      => "AND",
                "key"       => "mime_type",
                "value"     => $mime_type,
                "method"    => "="
            );
        }

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, "DESC");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
}
