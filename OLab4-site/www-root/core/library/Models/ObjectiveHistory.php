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
 * A model for handeling courses
 *
 * @author Organisation: University of Ottawa
 * @author Unit: Faculty of Medicine
 * @author Developer: Yacine Ghomri <yghomri@uottawa.ca>
 * @copyright Copyright 2017 University of Ottawa. All Rights Reserved.
 */

class Models_ObjectiveHistory extends Models_Base {

    private $objective_history_id;
    private $objective_id;
    private $proxy_id;
    private $history_message;
    private $history_display;
    private $history_timestamp;

    // Geters
    public function getObjectiveHistoryId () {
        return $this->objective_history_id;
    }
    public function getObjectiveId () {
        return $this->objective_id;
    }

    public function getProxyId() {
        return $this->proxy_id;
    }

    public function getHistoryMessage() {
        return $this->history_message;
    }

    public function getHistoryDisplay() {
        return $this->history_display;
    }

    public function getHistoryTimestamp() {
        return $this->history_timestamp;
    }

    public static function insertHistory($table, $data) {
        global $db;

        if ($db->AutoExecute($table, $data, "INSERT")) {
            return true;
        } else {
            return false;
        }
    }

    public static function fetchHistory($objective_id, $organisation_id, $history_display = 1) {
        global $db;

        $query = "SELECT his.*, CONCAT_WS(' ', user.`firstname`, user.`lastname`) AS `fullname`
            FROM `objective_history` AS his
            INNER JOIN `objective_organisation` AS org 
            ON his.`objective_id` = org.`objective_id`
            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS user 
            ON his.`proxy_id` = user.`id`
            WHERE his.`objective_id`  = ? 
            AND org.`organisation_id` = ?
            AND `history_display` = ?
            ORDER BY `history_timestamp` DESC, his.`objective_history_id` DESC";

        $history = $db->GetAll($query, array($objective_id, $organisation_id, $history_display));
        return $history;
    }
}
?>
