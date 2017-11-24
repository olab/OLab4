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
 * @author Organisation: Queen's University
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Schedule_Draft_Author extends Models_Base {

    protected $cbl_schedule_draft_author_id, $cbl_schedule_draft_id, $proxy_id, $created_date, $created_by;

    protected static $table_name       = "cbl_schedule_draft_authors";
    protected static $primary_key      = "cbl_schedule_draft_author_id";
    protected static $default_sort_column = "cbl_schedule_draft_author_id";

    public function getID() {
        return $this->cbl_schedule_draft_author_id;
    }

    public function getDraftID() {
        return $this->cbl_schedule_draft_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getUser() {
        return User::fetchRowByID($this->proxy_id);
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public static function fetchAllByDraftID($draft_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "cbl_schedule_draft_id", "value" => $draft_id, "method" => "=")
        ));
    }

    public static function fetchAllByProxyID($proxy_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "=")
        ));
    }

    public static function fetchRowByID($draft_author_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cbl_schedule_draft_author_id", "value" => $draft_author_id, "method" => "=")
        ));
    }

    public static function isAuthor($draft_id, $proxy_id) {
        global $db;
        $query = "SELECT * FROM `cbl_schedule_draft_authors` WHERE `cbl_schedule_draft_id` = ? AND `proxy_id` = ?";
        $result = $db->getRow($query, array($draft_id, $proxy_id));
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->draft_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }

    public function delete() {
        global $db;

        $query = "DELETE FROM `".static::$table_name."` WHERE `proxy_id` = ".$db->qstr($this->proxy_id)." AND `cbl_schedule_draft_id` = ".$db->qstr($this->cbl_schedule_draft_id)."";
        if ($db->Execute($query)) {
            return true;
        } else {
            return false;
        }
    }

}