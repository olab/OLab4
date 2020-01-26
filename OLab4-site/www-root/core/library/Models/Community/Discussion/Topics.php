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
 * Model for handling Discussion Topics
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queens.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Community_Discussion_Topics extends Models_Base {
    protected $cdtopic_id, $cdtopic_parent, $cdiscussion_id, $community_id, $proxy_id, $anonymous, $topic_title, $topic_description, $topic_active, $release_date, $release_until, $updated_date, $updated_by, $notify;

    protected static $table_name = "community_discussion_topics";
    protected static $primary_key = "cdtopic_id";
    protected static $default_sort_column = "cdtopic_parent";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cdtopic_id;
    }

    public function getCdtopicID() {
        return $this->cdtopic_id;
    }

    public function getCdtopicParent() {
        return $this->cdtopic_parent;
    }

    public function getCdiscussionID() {
        return $this->cdiscussion_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getAnonymous() {
        return $this->anonymous;
    }

    public function getTopicTitle() {
        return $this->topic_title;
    }

    public function getTopicDescription() {
        return $this->topic_description;
    }

    public function getTopicActive() {
        return $this->topic_active;
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

    public static function fetchRowByID($cdtopic_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cdtopic_id", "value" => $cdtopic_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($topic_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "topic_active", "value" => $topic_active, "method" => "=")));
    }
}