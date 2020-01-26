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
 * A model for handling Community Discussions Boards that are opened
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Discussion_Open extends Models_Base {
    protected   $cdopen_id,
                $community_id,
                $page_id,
                $proxy_id,
                $discussion_open;

    protected static $table_name = "community_discussions_open";
    protected static $primary_key = "cdopen_id";
    protected static $default_sort_column = "cdopen_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cdopen_id;
    }

    public function getCDOpenID() {
        return $this->cdopen_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getPageID() {
        return $this->page_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getDiscussionOpen() {
        return $this->discussion_open;
    }

    public function setDiscussionOpen($open) {
        $this->discussion_open = $open;
    }

    /* @return bool|Models_Community_Discussion */
    public static function fetchRowByID($cdopen_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cdopen_id", "value" => $cdopen_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Community_Discussion */
    public static function fetchRowByProxyIdPageId($proxy_id, $page_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "page_id", "value" => $page_id, "method" => "=")
        ));
    }
}