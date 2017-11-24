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
 * A model for handling the access records associated with users.
 *
 * @author Organisation: Queen's University
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_User_Department extends Models_Base {
    protected $udep_id, $user_id, $dep_id, $dep_title, $entrada_only;

    protected static $database_name = AUTH_DATABASE;
    protected static $table_name = "user_departments";
    protected static $primary_key = "udep_id";
    protected static $default_sort_column = "dep_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getUdepID() {
        return $this->udep_id;
    }

    public function getUserID() {
        return $this->user_id;
    }

    public function getDepID() {
        return $this->dep_id;
    }

    public function getDepTitle() {
        return $this->dep_title;
    }

    public function getEntradaOnly() {
        return $this->entrada_only;
    }
}
