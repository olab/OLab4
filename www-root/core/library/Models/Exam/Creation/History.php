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
 * A model for handling exam creation history.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2017 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Creation_History extends Models_Base {
    protected $exam_history_id,
        $exam_id,
        $proxy_id,
        $action,
        $action_resource_id,
        $secondary_action,
        $secondary_action_resource_id,
        $history_message,
        $timestamp;

    protected static $table_name = "exam_creation_history";
    protected static $primary_key = "exam_history_id";
    protected static $default_sort_column = "exam_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->exam_history_id;
    }

    public function getExamHistoryID() {
        return $this->exam_history_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getAction() {
        return $this->action;
    }

    public function getActionResourceID() {
        return $this->action_resource_id;
    }

    public function getSecondaryAction() {
        return $this->secondary_action;
    }

    public function getSecondaryActionResourceId() {
        return $this->secondary_action_resource_id;
    }

    public function getHistoryMessage() {
        return $this->history_message;
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    /* @return bool|Models_Exam_Creation_History */
    public static function fetchRowByID($exam_history_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_history_id", "value" => $exam_history_id, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Exam_Creation_History[] */
    public static function fetchAllByExamId($exam_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Exam_Creation_History[] */
    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_history_id", "value" => 0, "method" => ">=")
        ));
    }
}