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
 * A model to handle interaction with the draft creators.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Event_Draft_Creator extends Models_Base {
    
    protected $create_id, $draft_id, $proxy_id;

    protected static $table_name           = "draft_creators";
    protected static $primary_key          = "create_id";
    protected static $default_sort_column  = "proxy_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->create_id;
    }

    public function getCreateID() {
        return $this->create_id;
    }
    
    public function getDraftID() {
        return $this->draft_id;
    }
    
    public function getProxyID() {
        return $this->proxy_id;
    }
    
    public function getCreator() {
        return User::fetchRowByID($this->proxy_id);
    }
    
    public static function fetchRowByID($create_id = 0) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "create_id", "value" => $create_id, "method" => "=", "mode" => "AND")
            )
        );
    }
    
    public static function fetchRowByDraftIDProxyID($draft_id, $proxy_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "draft_id", "value" => $draft_id, "method" => "=", "mode" => "AND"),
                array("key" => "proxy_id", "value" => $proxy_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllByDraftID($draft_id = 0) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "draft_id", "value" => $draft_id, "method" => "=", "mode" => "AND")
            )
        );
    }
}