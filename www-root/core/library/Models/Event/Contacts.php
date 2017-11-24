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
 * A model for handling event contacts.
 * 
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 David Geffen School of Medicine at UCLA
 *
 */

class Models_Event_Contacts extends Models_Base {
    protected $econtact_id, $event_id, $proxy_id, $contact_role, $contact_order, $updated_date, $updated_by;
    
    protected static $table_name            = "event_contacts";
    protected static $primary_key           = "econtact_id";
    protected static $default_sort_column   = "contact_order";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->econtact_id;
    }

    public function getEcontactID() {
        return $this->econtact_id;
    }

    public function getEventID() {
        return $this->event_id;
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
    
    public static function fetchRowByID($econtact_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "econtact_id", "value" => $econtact_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "econtact_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByEventID($event_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "event_id", "value" => $event_id, "method" => "=")), "=", "AND", "contact_order", "ASC");
    }
    
    public static function fetchRowByEventIDProxyID($event_id = 0, $proxy_id = 0) {
        $self = new self();

        $constraints = array(       
            array(
            "key"       => "event_id" ,
            "value"     => $event_id,
            "method"    => "="
            ),
            array(
            "mode"      => "AND",
            "key"       => "proxy_id" ,
            "value"     => $proxy_id,
            "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
}