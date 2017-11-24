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
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Migration extends Models_Base {
    protected $migration;
    protected $batch = 1;
    protected $success = 0;
    protected $fail = 0;
    protected $updated_date;

    protected static $table_name = "migrations";
    protected static $primary_key = "migration";
    protected static $default_sort_column = "migration";

    public function __construct($arr = NULL) {
        $this->updated_date = time();

        parent::__construct($arr);
    }

    public function getID() {
        return $this->migration;
    }

    public function getMigration() {
        return $this->migration;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getDatabaseMigrations($migration_index = array(), $include_all = false) {
        global $db;

        if (!$include_all && is_array($migration_index) && !empty($migration_index)) {
            $query = "SELECT *
                        FROM `migrations`
                        WHERE `migration` IN ('" . implode("', '", $migration_index) . "')
                        ORDER BY `migration` ASC";
        } else {
            $query = "SELECT *
                        FROM `migrations`
                        ORDER BY `migration` ASC";
        }

        return $db->GetAll($query);
    }

    public function fetchLastMigration() {
        global $db;

        $query = "SELECT `migration`
                    FROM `migrations`
                    ORDER BY `migration` DESC
                    LIMIT 1";
        return $db->GetOne($query);
    }

    public function delete($migration = "") {
        global $db;

        if ($migration) {
            $query = "DELETE FROM `migrations` WHERE `migration` = ?";
            if ($db->Execute($query, $migration)) {
                return true;
            }
        }

        return false;
    }

    public static function fetchRowByID($migration) {
        $self = new self();
        return $self->fetchRow(array(
            array(
                "key" => "migration",
                "method" => "=",
                "value" => $migration
            )
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array());
    }
}