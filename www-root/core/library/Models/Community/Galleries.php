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
 * Model for handling Community Galleries
 *
 * @author Organisation: 
 * @author Developer: Frederic Turmel <ft11@queens.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Community_Galleries extends Models_Base {
    protected $cgallery_id, $community_id, $cpage_id, $gallery_title, $gallery_description, $gallery_cgphoto_id, $gallery_order, $gallery_active, $admin_notifications, $allow_public_read, $allow_public_upload, $allow_public_comment, $allow_troll_read, $allow_troll_upload, $allow_troll_comment, $allow_member_read, $allow_member_upload, $allow_member_comment, $release_date, $release_until, $updated_date, $updated_by;

    protected static $table_name = "community_galleries";
    protected static $primary_key = "cgallery_id";
    protected static $default_sort_column = "community_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cgallery_id;
    }

    public function getCgalleryID() {
        return $this->cgallery_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getCpageID() {
        return $this->cpage_id;
    }

    public function getGalleryTitle() {
        return $this->gallery_title;
    }

    public function getGalleryDescription() {
        return $this->gallery_description;
    }

    public function getGalleryCgphotoID() {
        return $this->gallery_cgphoto_id;
    }

    public function getGalleryOrder() {
        return $this->gallery_order;
    }

    public function getGalleryActive() {
        return $this->gallery_active;
    }

    public function getAdminNotifications() {
        return $this->admin_notifications;
    }

    public function getAllowPublicRead() {
        return $this->allow_public_read;
    }

    public function getAllowPublicUpload() {
        return $this->allow_public_upload;
    }

    public function getAllowPublicComment() {
        return $this->allow_public_comment;
    }

    public function getAllowTrollRead() {
        return $this->allow_troll_read;
    }

    public function getAllowTrollUpload() {
        return $this->allow_troll_upload;
    }

    public function getAllowTrollComment() {
        return $this->allow_troll_comment;
    }

    public function getAllowMemberRead() {
        return $this->allow_member_read;
    }

    public function getAllowMemberUpload() {
        return $this->allow_member_upload;
    }

    public function getAllowMemberComment() {
        return $this->allow_member_comment;
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

    public static function fetchRowByID($cgallery_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cgallery_id", "value" => $cgallery_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($gallery_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "gallery_active", "value" => $gallery_active, "method" => "=")));
    }
}