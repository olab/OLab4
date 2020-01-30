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
 * A model to handle learning object statistics.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_LearningObject_Progress extends Models_Base {

    protected $learning_objects_progress_id,
        $proxy_id,
        $learning_objects_activity_id,
        $learning_objects_state_id,
        $data,
        $created_date;

    protected static $table_name = "learning_objects_progress";
    protected static $default_sort_column = "learning_objects_progress_id";
    protected static $primary_key = "learning_objects_progress_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getLoProgressID() {
        return $this->learning_objects_progress_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getLoActivityID() {
        return $this->learning_objects_activity_id;
    }

    public function getLoStateID() {
        return $this->learning_objects_state_id;
    }

    public function getData() {
        return $this->data;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "learning_objects_progress_id", "value" => $id, "method" => "=")
        ));
    }

    public function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "learning_objects_progress_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchLastByProxyIDActivityID($proxy_id, $activity_id, $state_id) {
        global $db;

        $query = "SELECT * FROM `learning_objects_progress` 
        WHERE `proxy_id` = ? 
        AND `learning_objects_activity_id` = ? 
        AND `learning_objects_state_id` = ? 
        ORDER BY `created_date` DESC, `learning_objects_progress_id` DESC LIMIT 1";

        $result = $db->GetRow($query, array($proxy_id, $activity_id, $state_id));
        if ($result) {
            return $result;
        }

        return 0;
    }
}
