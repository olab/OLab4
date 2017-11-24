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
 * Model to handle Community Shares Comments
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queens.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Community_Share_Comments extends Models_Base {
    protected $cscomment_id, $csfile_id, $cshare_id, $community_id, $proxy_id, $comment_title, $comment_description, $comment_active, $release_date, $updated_date, $updated_by, $notify;

    protected $table_name = "community_share_comments";
    protected $primary_key = "cscomment_id";
    protected $default_sort_column = "csfile_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cscomment_id;
    }

    public function getCscommentID() {
        return $this->cscomment_id;
    }

    public function getCsfileID() {
        return $this->csfile_id;
    }

    public function getCshareID() {
        return $this->cshare_id;
    }

    public function getCommunityID() {
        return $this->community_id;
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

    public static function fetchRowByID($cscomment_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cscomment_id", "value" => $cscomment_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($comment_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "comment_active", "value" => $comment_active, "method" => "=")));
    }
}