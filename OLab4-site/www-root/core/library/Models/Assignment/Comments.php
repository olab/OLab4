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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assignment_Comments extends Models_Base {
    protected $acomment_id, $proxy_to_id, $assignment_id, $proxy_id, $comment_title, $comment_description, $comment_active, $release_date, $updated_date, $updated_by, $notify = 0;

    protected static $table_name = "assignment_comments";
    protected static $primary_key = "acomment_id";
    protected static $default_sort_column = "proxy_to_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->acomment_id;
    }

    public function getAcommentID() {
        return $this->acomment_id;
    }

    public function getProxyToID() {
        return $this->proxy_to_id;
    }

    public function getAssignmentID() {
        return $this->assignment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getCommentTitle() {
        return $this->comment_title;
    }

    public function getCommentDescription() {
        return $this->comment_description;
    }

    public function getCommentActive() {
        return $this->comment_active;
    }

    public function getReleaseDate() {
        return $this->release_date;
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

    public static function fetchRowByID($acomment_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "acomment_id", "value" => $acomment_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($comment_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "comment_active", "value" => $comment_active, "method" => "=")));
    }

    public static function getAllByAssignmentIDProxyID($assignment_id, $proxy_id, $comment_active = true) {
        global $db;

        $query = "SELECT DISTINCT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `commenter_fullname`, b.`username` AS `commenter_username`, b.`id` AS `proxy_id`
                        FROM `assignment_comments` AS a
                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                        ON b.`id` = a.`proxy_id`
                        WHERE a.`proxy_to_id` = ?
                        AND a.`assignment_id` = ?
                        AND a.`comment_active` = ?
                        ORDER BY a.`release_date` ASC";

        $assignment_files = $db->GetAll($query, array($proxy_id, $assignment_id, $comment_active));

        if ($assignment_files) {
            return $assignment_files;
        }

        return false;
    }
}