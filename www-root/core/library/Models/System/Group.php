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

class Models_System_Group extends Models_Base  {
    protected   $id,
                $group_name;
    
    protected static $primary_key = "id";
    protected static $table_name = "system_groups";
    protected static $default_sort_column = "group_name";
    protected static $database_name = AUTH_DATABASE;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }
    
    public function getGroupName() {
        return $this->group_name;
    }

    /* @return bool|Models_System_Group */
    public static function fetchRowByID($group_id) {
        $self = new self();

        return $self->fetchRow(array(
            array("key" => "id", "value" => $group_id, "method" => "=")
        ));
    }

    public static function fetchRowByName($group_name, $organisation_id, $active = 1) {
        global $db;

        $organisation_id = (int) $organisation_id;
        $active = (($active == 1) ? 1 : 0);

        $query = "SELECT a.*
                    FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `".static::$database_name."`.`system_group_organisation` AS b
                    ON a.`id` = b.`groups_id`
                    WHERE a.`group_name` = ?
                    AND b.`organisation_id` = ?
                    AND a.`visible` = ?";

        return $db->GetRow($query, array($group_name, $organisation_id, $active));
    }

    public static function fetchAllByOrganisationID($organisation_id, $visible = 1) {
        global $db;

        $visible = (int) $visible;

        $query = "SELECT a.*
                    FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `".static::$database_name."`.`system_group_organisation` AS b
                    ON a.`id` = b.`groups_id`
                    WHERE a.`visible` = ?
                    AND b.`organisation_id` = ?";

        return $db->GetAll($query, array($visible, $organisation_id));
    }
}
