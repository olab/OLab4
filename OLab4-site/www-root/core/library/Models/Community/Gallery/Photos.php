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
 * Model for handling community galery photos
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queens.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Community_Gallery_Photos extends Models_Base {
    protected $cgphoto_id, $cgallery_id, $community_id, $proxy_id, $photo_mimetype, $photo_filename, $photo_filesize, $photo_title, $photo_description, $photo_active, $release_date, $release_until, $updated_date, $updated_by, $notify;

    protected static $table_name = "community_gallery_photos";
    protected static $primary_key = "cgphoto_id";
    protected static $default_sort_column = "cgallery_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cgphoto_id;
    }

    public function getCgphotoID() {
        return $this->cgphoto_id;
    }

    public function getCgalleryID() {
        return $this->cgallery_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getPhotoMimetype() {
        return $this->photo_mimetype;
    }

    public function getPhotoFilename() {
        return $this->photo_filename;
    }

    public function getPhotoFilesize() {
        return $this->photo_filesize;
    }

    public function getPhotoTitle() {
        return $this->photo_title;
    }

    public function getPhotoDescription() {
        return $this->photo_description;
    }

    public function getPhotoActive() {
        return $this->photo_active;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getNotify() {
        return $this->notify;
    }

    public static function fetchRowByID($cgphoto_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cgphoto_id", "value" => $cgphoto_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($photo_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "photo_active", "value" => $photo_active, "method" => "=")));
    }
}