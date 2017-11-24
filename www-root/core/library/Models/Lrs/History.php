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
 * A model to handle recording and fetching from lrs_history table.
 *
 * @author Organisation: Queen's University
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Models_Lrs_History extends Models_Base {

    protected $id, $type, $run_last;

    protected static $table_name = "lrs_history";
    protected static $primary_key = "id";
    protected static $default_sort_column = "timestamp";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    /**
     * This method returns the run_last date (Unix Timestamp) for the provided type.
     *
     * @param string $type
     * @return int
     */
    public function runLast($type = "") {
        global $db;

        $query = "SELECT `run_last` FROM `lrs_history` WHERE `type` = ? ORDER BY `run_last` DESC";
        $result = $db->GetRow($query, array($type));
        if ($result) {
            return $result["run_last"];
        }

        return 0;
    }
}
