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
 * A model for handling Course Groups
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_System_Role extends Models_Base  {
    protected   $id,
                $role_name,
                $groups_id;
    
    protected static $primary_key = "id";
    protected static $table_name = "system_roles";
    protected static $default_sort_column = "role_name";
    protected static $database_name = AUTH_DATABASE;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }
    
    public function getRoleName() {
        return $this->role_name;
    }

    /*
     * Don't judge me.
     */
    public function getGroupID() {
        return $this->groups_id;
    }

    public function getGroupsID() {
        return $this->groups_id;
    }

    /* @return bool|Models_System_Role */
    public static function fetchRowByID($role_id) {
        $self = new self();

        return $self->fetchRow(array(
            array("key" => "id", "value" => $role_id, "method" => "=")
        ));
    }

    public static function fetchAllByGroupID($group_id = 0, $organisation_id = 0, $visible = 1, $include_empty = false) {
        global $db;

        $group_id = (int) $group_id;
        $organisation_id = (int) $organisation_id;
        $visible = (int) $visible;
        $include_empty = (bool) $include_empty;

        $query = "SELECT a.*
                    FROM `".static::$database_name."`.`system_roles` AS a
                    JOIN `".static::$database_name."`.`system_groups` AS b
                    ON a.`groups_id` = b.`id`
                    JOIN `".static::$database_name."`.`system_group_organisation` AS c
                    ON b.`id` = c.`groups_id`
                    WHERE b.`id` = ?
                    AND b.`visible` = ?
                    AND c.`organisation_id` = ?
                    ".(!$include_empty ? " AND EXISTS (SELECT id FROM `".static::$database_name."`.`user_access` WHERE `account_active` = true AND `group` = b.`group_name` AND `role` = a.`role_name` AND `organisation_id` = c.`organisation_id`)" : "")."
                    GROUP BY a.`id`
                    ORDER BY a.`role_name` ASC";

        return $db->GetAll($query, array($group_id, $visible, $organisation_id));
    }

    public static function fetchAllByOrganisationID($organisation_id, $visible = 1) {
        global $db;

        $visible = (int) $visible;

        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `system_groups` AS b
                    ON a.`groups_id` = b.`id`
                    JOIN `system_group_organisation` AS c
                    ON b.`id` = c.`groups_id`
                    WHERE b.`visible` = ?
                    AND c.`organisation_id` = ?";

        return $db->GetAll($query, array($visible, $organisation_id));
    }
}
