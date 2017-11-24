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
 * A model to handle draft contacts
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */
class Models_Event_Draft_Contacts extends Models_Base {

    protected   $dcontact_id,
                $econtact_id,
                $event_id,
                $devent_id,
                $proxy_id,
                $contact_role,
                $contact_order,
                $updated_date,
                $updated_by;
    
    protected static $table_name           = "draft_contacts";
    protected static $primary_key          = "dcontact_id";
    protected static $default_sort_column  = "contact_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->dcontact_id;
    }

    public function getDraftID() {
        return $this->dcontact_id;
    }

    public function getEventID() {
        return $this->event_id;
    }
    
    public function getDraftEventID() {
        return $this->devent_id;
    }
    
    public function getProxyID() {
        return $this->proxy_id;
    }
    
    public function getContactRole() {
        return $this->contact_role;
    }
    
    public function getContactOrder() {
        return $this->contact_order;
    } 
    
    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public static function fetchAllByEventID($event_id = 0) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "event_id", "value" => $event_id, "method" => "=")
        ));
    }
    
    public static function fetchRowByEventIDProxyID($event_id = 0, $proxy_id = 0) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "=")
        ));
    }

    public function delete() {
        global $db;

        $query = "DELETE FROM `" . $this->table_name . "` WHERE `dcontact_id` = ?";
        $result = $db->Execute($query, array($this->getID()));

        return $result;
    }
}