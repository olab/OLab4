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
 * Model to handle community polls
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Community_Polls extends Models_Base {
    protected $cpolls_id, $community_id, $cpage_id, $proxy_id, $poll_title, $poll_description, $poll_terminology, $poll_active, $poll_order, $poll_notifications, $allow_multiple, $number_of_votes, $allow_public_read, $allow_public_vote, $allow_public_results, $allow_public_results_after, $allow_troll_read, $allow_troll_vote, $allow_troll_results, $allow_troll_results_after, $allow_member_read, $allow_member_vote, $allow_member_results, $allow_member_results_after, $release_date, $release_until, $updated_date, $updated_by;

    protected static $table_name = "community_polls";
    protected static $primary_key = "cpolls_id";
    protected static $default_sort_column = "cpolls_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cpolls_id;
    }

    public function getCpollsID() {
        return $this->cpolls_id;
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

    public function getPollTitle() {
        return $this->poll_title;
    }

    public function getPollDescription() {
        return $this->poll_description;
    }

    public function getPollTerminology() {
        return $this->poll_terminology;
    }

    public function getPollActive() {
        return $this->poll_active;
    }

    public function getPollOrder() {
        return $this->poll_order;
    }

    public function getPollNotifications() {
        return $this->poll_notifications;
    }

    public function getAllowMultiple() {
        return $this->allow_multiple;
    }

    public function getNumberOfVotes() {
        return $this->number_of_votes;
    }

    public function getAllowPublicRead() {
        return $this->allow_public_read;
    }

    public function getAllowPublicVote() {
        return $this->allow_public_vote;
    }

    public function getAllowPublicResults() {
        return $this->allow_public_results;
    }

    public function getAllowPublicResultsAfter() {
        return $this->allow_public_results_after;
    }

    public function getAllowTrollRead() {
        return $this->allow_troll_read;
    }

    public function getAllowTrollVote() {
        return $this->allow_troll_vote;
    }

    public function getAllowTrollResults() {
        return $this->allow_troll_results;
    }

    public function getAllowTrollResultsAfter() {
        return $this->allow_troll_results_after;
    }

    public function getAllowMemberRead() {
        return $this->allow_member_read;
    }

    public function getAllowMemberVote() {
        return $this->allow_member_vote;
    }

    public function getAllowMemberResults() {
        return $this->allow_member_results;
    }

    public function getAllowMemberResultsAfter() {
        return $this->allow_member_results_after;
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

    public static function fetchRowByID($cpolls_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cpolls_id", "value" => $cpolls_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($poll_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "poll_active", "value" => $poll_active, "method" => "=")));
    }
}