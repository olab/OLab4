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
 * A model for handling assignment contacts
 *
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 bitHeads, Inc. All Rights Reserved.
 */

class Models_Assignment_Contact extends Models_Base {
    protected $acontact_id, $assignment_id, $proxy_id, $contact_order, $updated_date, $updated_by;

    protected static $table_name = "assignment_contacts";
    protected static $primary_key = "acontact_id";
    protected static $default_sort_column = "assignment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->acontact_id;
    }

    public function getAcontactID() {
        return $this->acontact_id;
    }

    public function getAssignmentID() {
        return $this->assignment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
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

    public static function fetchRowByID($acontact_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "acontact_id", "value" => $acontact_id, "method" => "=")
        ));
    }

    public function fetchAllByAssignmentID() {
        return $this->fetchAll(array(
            array("key" => "assignment_id", "value" => $this->assignment_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "acontact_id", "value" => 0, "method" => ">=")));
    }
}