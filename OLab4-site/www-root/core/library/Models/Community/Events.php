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
 * Model for handling the community events
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queens.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Community_Events extends Models_Base {
    protected $cevent_id, $community_id, $cpage_id, $proxy_id, $event_active, $pending_moderation,$event_start,
        $event_finish, $event_location, $release_date, $release_until, $event_title, $event_description, $updated_date,
        $updated_by;

    protected static $table_name = "community_events";
    protected static $primary_key = "cevent_id";
    protected static $default_sort_column = "community_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cevent_id;
    }

    public function getCeventID() {
        return $this->cevent_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getCpageID() {
        return $this->cpage_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getEventActive() {
        return $this->event_active;
    }

    public function getPendingModeration() {
        return $this->pending_moderation;
    }

    public function getEventStart() {
        return $this->event_start;
    }

    public function getEventFinish() {
        return $this->event_finish;
    }

    public function getEventLocation() {
        return $this->event_location;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getEventTitle() {
        return $this->event_title;
    }

    public function getEventDescription() {
        return $this->event_description;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($cevent_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cevent_id", "value" => $cevent_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($event_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "event_active", "value" => $event_active, "method" => "=")));
    }
}