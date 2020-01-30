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


class Models_User_Photo extends Models_Base {
    protected $photo_id, $proxy_id, $photo_mimetype, $photo_active, $photo_type, $updated_date;

    const OFFICIAL = "official";
    const UPLOADED = "upload";

    public static $types = array(self::OFFICIAL, self::UPLOADED);

    protected static $database_name = AUTH_DATABASE;
    protected static $table_name = "user_photos";
    protected static $primary_key = "photo_id";
    protected static $default_sort_column = "photo_id";


    function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function isActive() {
        return (bool) $this->photo_active;
    }

    public function getID() {
        return $this->photo_id;
    }

    public function getUserID() {
        return $this->proxy_id;
    }

    public function getMimetype() {
        return $this->photo_mimetype;
    }

    public function getFilesize() {
        return $this->filesize();
    }

    public function getPhotoType() {
        return (1 === $this->photo_type) ? self::UPLOADED : self::OFFICIAL;
    }

    public function getFilename() {
        return webservice_url("photo", array($this->getUserID(), $this->getPhotoType()));
    }

    public static function get($user_id, $type = self::OFFICIAL) {
        if (in_array($type, self::$types)) { //validate type
            if (@file_exists(STORAGE_USER_PHOTOS."/".$user_id."-".$type)) { //validate file existence
                //the two types get handled differently -- UPLOADED files are in the DB, OFFICIAL files are not, and some info has to be manufactured
                switch ($type) {
                    case self::OFFICIAL:
                        $photo = array(
                            "proxy_id" => $user_id,
                            "photo_filesize" => @filesize(STORAGE_USER_PHOTOS."/".$user_id."-".$type),
                            "photo_active" => 1,
                            "photo_type" => 0
                        );
                        return $photo;
                        break;
                    case self::UPLOADED:
                        $self = new self();
                        return $self->fetchRow(array(
                            array("key" => "proxy_id", "value" => $user_id, "method" => "=")
                        ));
                        break;
                }
            }

            return false;
        }
    }
}