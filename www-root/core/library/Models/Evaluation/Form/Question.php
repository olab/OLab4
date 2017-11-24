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
 * A model for handling evaluation form questions
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Evaluation_Form_Question extends Models_Base {
    protected $efquestion_id, $eform_id, $equestion_id, $question_order, $allow_comments, $send_threshold_notifications;

    protected static $table_name = "evaluation_form_questions";
    protected static $primary_key = "efquestion_id";
    protected static $default_sort_column = "question_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->efquestion_id;
    }

    public function getEfquestionID() {
        return $this->efquestion_id;
    }

    public function getEformID() {
        return $this->eform_id;
    }

    public function getEquestionID() {
        return $this->equestion_id;
    }

    public function getQuestionOrder() {
        return $this->question_order;
    }

    public function getAllowComments() {
        return $this->allow_comments;
    }

    public function getSendThresholdNotifications() {
        return $this->send_threshold_notifications;
    }

    public static function fetchRowByID($efquestion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "efquestion_id", "value" => $efquestion_id, "method" => "=")
        ));
    }


    public static function fetchRowByFormIDQuestionID($eform_id, $equestion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "equestion_id", "value" => $equestion_id, "method" => "="),
            array("key" => "eform_id", "value" => $eform_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "efquestion_id", "value" => 0, "method" => ">=")));
    }
}