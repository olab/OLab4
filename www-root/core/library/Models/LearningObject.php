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
 * A model to handle learning objects.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_LearningObject extends Models_Base {

    protected $lo_file_id, $filename, $filesize, $mime_type, $description, $proxy_id, $public = 0,
              $updated_date, $updated_by, $active = 1;

    protected static $primary_key = "lo_file_id";
    protected static $table_name = "learning_object_files";
    protected static $default_sort_column = "updated_date";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
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
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->lo_file_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`lo_file_id` = " . $db->qstr($this->lo_file_id))) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function getLoFileID() {
        return $this->lo_file_id;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getFilesize() {
        return $this->filesize;
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
    
    public static function generateLearningObjectThumbnail($file_realpath, $mime_type, $thumb_width = "150") {
        $thumbnail_realpath = substr($file_realpath, 0, strripos($file_realpath, "/") + 1) . "thumbnails/" . substr($file_realpath, strripos($file_realpath, "/") + 1, strlen($file_realpath));
        
        switch ($mime_type) {
            case "image/jpeg":
                $image = imagecreatefromjpeg($file_realpath);
            break;
            case "image/png":
                $image = imagecreatefrompng($file_realpath);
            break;
            case "image/gif":
                $image = imagecreatefromgif($file_realpath);
            break;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        $new_width  = $thumb_width;
        $new_height = floor($height * ($thumb_width / $width));

        $tmp_img    = imagecreatetruecolor($new_width, $new_height);
        $background = imagecolorallocate($tmp_img, 0, 0, 0);

        imagecolortransparent($tmp_img, $background);
        imagealphablending($tmp_img, false);
        imagesavealpha($tmp_img, true);

        imagecopyresized($tmp_img, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        if (imagepng($tmp_img, $thumbnail_realpath)) {
            return true;
        } else {
            return false;
        }
    }
    
}

?>
