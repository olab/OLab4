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
 * A model for handling course contacts.
 *
 * @author Organisation: Queen's University
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Profile_Custom_Responses extends Models_Base {
    protected $id, $field_id, $proxy_id, $value;

    protected static $table_name = "profile_custom_responses";
    protected static $primary_key = "id";
    protected static $default_sort_column = "id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

    public function getFieldID() {
        return $this->field_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getValue() {
        return $this->value;
    }

    public function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "=")
        ));
    }

    public function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "id", "value" => 0, "method" => ">=")));
    }

    public static function getAllByByOrganisationIDOrdered($organisation_id) {
        global $db;

        $query = "SELECT * FROM `profile_custom_fields` WHERE `organisation_id` = ? ORDER BY `organisation_id`, `department_id`, `id`";
        $results = $db->GetAssoc($query, array($organisation_id));

        if ($results) {
            return $results;
        }

        return false;
    }

    public static function deleteByFieldIDProxyID($field_id, $proxy_id) {
        global $db;

        $query = "DELETE FROM `profile_custom_responses` WHERE `field_id` = ? AND `proxy_id` = ? ";
        $result = $db->Execute($query, array($field_id, $proxy_id));

        if ($result) {
            return $result;
        }

        return false;
    }

}