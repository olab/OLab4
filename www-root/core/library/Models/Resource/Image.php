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
 * A model for handling course contacts.
 *
 * @author Organisation: Queen's University
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Resource_Image extends Models_Base {
    protected $image_id, $resource_id, $image_mimetype, $image_filesize, $image_active, $resource_type, $updated_date;

    protected static $table_name = "resource_images";
    protected static $primary_key = "image_id";
    protected static $default_sort_column = "image_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getImageID() {
        return $this->image_id;
    }

    public function getResourceID() {
        return $this->resource_id;
    }

    public function getImageMimetype() {
        return $this->image_mimetype;
    }

    public function getImageFilesize() {
        return $this->image_filesize;
    }

    public function getImageActive() {
        return $this->image_active;
    }

    public function getResourceType() {
        return $this->resource_type;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "image_id", "value" => $id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "image_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchRowByResourceIDResourceType($resource_id, $image_type = "course") {
        $self = new self();
        $constraints = array(
            array("key" => "resource_id", "value" => $resource_id, "method" => "="),
            array("key" => "resource_type", "value" => $image_type, "method" => "="),
        );
        return $self->fetchRow($constraints);
    }
}
