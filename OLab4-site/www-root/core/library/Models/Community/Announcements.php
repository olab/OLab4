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
 * Model for handling communities annoucements
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queens.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Community_Announcements extends Models_Base {
    protected $cannouncement_id, $community_id, $cpage_id, $proxy_id, $announcement_active, $pending_moderation, $on_calendar, $event_start, $event_finish, $event_location, $release_date, $release_until, $announcement_title, $announcement_description, $updated_date, $updated_by;

    protected static $table_name = "community_announcements";
    protected static $primary_key = "cannouncement_id";
    protected static $default_sort_column = "community_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cannouncement_id;
    }

    public function getCannouncementID() {
        return $this->cannouncement_id;
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

    public function getAnnouncementActive() {
        return $this->announcement_active;
    }

    public function getPendingModeration() {
        return $this->pending_moderation;
    }

    public function getOnCalendar() {
        return $this->on_calendar;
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

    public function getAnnouncementTitle() {
        return $this->announcement_title;
    }

    public function getAnnouncementDescription() {
        return $this->announcement_description;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($cannouncement_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cannouncement_id", "value" => $cannouncement_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($announcement_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "announcement_active", "value" => $announcement_active, "method" => "=")));
    }
}