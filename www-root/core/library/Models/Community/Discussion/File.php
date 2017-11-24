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
 * A model for handling Community Discussions Files that are uploaded
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Discussion_File extends Models_Base {
    protected   $cdfile_id,
        $cdtopic_id,
        $cdiscussion_id,
        $community_id,
        $proxy_id,
        $file_title,
        $file_description,
        $file_active,
        $allow_member_revision,
        $allow_troll_revision,
        $access_method,
        $release_date,
        $release_until,
        $updated_date,
        $updated_by,
        $notify;

    protected static $table_name = "community_discussions_files";
    protected static $primary_key = "cdfile_id";
    protected static $default_sort_column = "cdfile_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cdfile_id;
    }

    public function getCDFileID() {
        return $this->cdfile_id;
    }

    public function getCdTopicID() {
        return $this->cdtopic_id;
    }

    public function getCDiscussionID() {
        return $this->cdiscussion_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getFileTitle() {
        return $this->file_title;
    }

    public function getFileDescription() {
        return $this->file_description;
    }

    public function getFileActive() {
        return $this->file_active;
    }

    public function getAllowMemberRevision() {
        return $this->allow_member_revision;
    }

    public function getAllowTrollRevision() {
        return $this->allow_troll_revision;
    }

    public function getAccessMethod() {
        return $this->access_method;
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

    /* @return bool|Models_Community_Discussion_File */
    public static function fetchRowByID($cdfile_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cdfile_id", "value" => $cdfile_id, "method" => "=")
        ));
    }
}