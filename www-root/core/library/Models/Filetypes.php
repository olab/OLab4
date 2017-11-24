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
 * Model for handling Assignment's comments
 *
 * @author Organisation: Queen's University
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Filetypes extends Models_Base {
    protected $id, $ext, $mime, $classname, $english, $image, $hidden;

    protected static $table_name = "filetypes";
    protected static $primary_key = "id";
    protected static $default_sort_column = "id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

    public function getExt() {
        return $this->ext;
    }

    public function getMime() {
        return $this->mime;
    }

    public function getClassname() {
        return $this->classname;
    }

    public function getEnglish() {
        return $this->english;
    }

    public function getImage() {
        return $this->image;
    }

    public function getHidden() {
        return $this->hidden;
    }

    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($hidden = 0) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "id", "value" => 0, "method" => ">="),
            array("key" => "hidden", "value" => $hidden, "method" => "="),
        ));
    }

}
