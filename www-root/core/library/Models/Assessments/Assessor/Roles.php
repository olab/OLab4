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
 * A model for handling assessors roles
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Assessor_Roles extends Models_Base {
    protected $role_id, $role_title, $role_description, $active, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $deleted_by;

    protected static $table_name = "cbl_assessor_roles";
    protected static $primary_key = "role_id";
    protected static $default_sort_column = "role_title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->role_id;
    }

    public function getRoleID() {
        return $this->role_id;
    }

    public function getRoleTitle() {
        return $this->role_title;
    }

    public function getRoleDescription() {
        return $this->role_description;
    }

    public function getActive() {
        return $this->active;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public static function fetchRowByID($role_id, $active=1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "role_id", "value" => $role_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ));
    }

    public static function fetchAllRecords($active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    public static function fetchAllByOrganisation($organisation_id, $active=1) {
        global $db;

        if (! intval($organisation_id)) {
            return false;
        }

        $query = "SELECT a.role_id FROM `".DATABASE_NAME."`.`".self::$table_name."` a
                  JOIN `".DATABASE_NAME."`.`cbl_assessor_role_organisation` b ON a.`role_id` = b.`role_id`
                  WHERE b.`organisation_id` = ?
                  AND a.active = ?";

        $results = $db->getCol($query, array($organisation_id, $active=1));
        if (!$results || ! is_array($results) || !count($results)) {
            return false;
        }

        $return = array();
        foreach ($results as $result) {
            $return[] = self::fetchRowByID($result, $active);
        }

        return $return;
    }
}