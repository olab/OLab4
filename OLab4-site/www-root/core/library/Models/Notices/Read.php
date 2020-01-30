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
 * A model for handling read notices
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Notices_Read extends Models_Base {

    protected $notice_read_id;
    protected $proxy_id;
    protected $notice_id;
    protected $created_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "notices_read";
    protected static $primary_key = "notice_read_id";
    protected static $default_sort_column = "proxy_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->notice_read_id;
    }

    public function getNoticeReadID() {
        return $this->notice_read_id;
    }

    public function setNoticeReadID($notice_read_id) {
        $this->notice_read_id = $notice_read_id;

        return $this;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function setProxyID($proxy_id) {
        $this->proxy_id = $proxy_id;

        return $this;
    }

    public function getNoticeID() {
        return $this->notice_id;
    }

    public function setNoticeID($notice_id) {
        $this->notice_id = $notice_id;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public static function fetchRowByID($notice_read_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "notice_read_id", "method" => "=", "value" => $notice_read_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "notice_read_id", "method" => ">=", "value" => 0)));
    }

    /**
     * Insert a new notices_read record for the specified notice_id. The proxy_id will default to the currently
     * logged in user when available, and the created_date will default to the current timestamp.
     *
     * @param $notice_id
     * @param null $proxy_id
     * @param null $created_date
     * @return $this|bool|Models_Notices_Read
     */
    public static function create($notice_id, $proxy_id = null, $created_date = null) {
        global $ENTRADA_USER;
        if (!isset($proxy_id) && isset($ENTRADA_USER) && $ENTRADA_USER) {
            $proxy_id = $ENTRADA_USER->getActiveID();
        }

        if ($proxy_id) {
            $notice_read = new self(array(
                "proxy_id"      => $proxy_id,
                "notice_id"     => $notice_id,
                "created_date"  => isset($created_date) ? $created_date : time()
            ));
            if ($notice_read = $notice_read->insert()) {
                return $notice_read;
            }
        } else {
            application_log("error", "Attempted to create a new notices_read without a proxy_id.");
        }

        return false;
    }

}