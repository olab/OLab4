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
 * A model for handling the evaluation question responses lookup
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Evaluation_Question_Response extends Models_Base {
    protected $eqresponse_id, $equestion_id, $response_text, $response_order, $response_is_html, $minimum_passing_level;

    protected static $table_name = "evaluations_lu_question_responses";
    protected static $primary_key = "eqresponse_id";
    protected static $default_sort_column = "response_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->eqresponse_id;
    }

    public function getEqresponseID() {
        return $this->eqresponse_id;
    }

    public function getEquestionID() {
        return $this->equestion_id;
    }

    public function getResponseText() {
        return $this->response_text;
    }

    public function getResponseOrder() {
        return $this->response_order;
    }

    public function getResponseIsHtml() {
        return $this->response_is_html;
    }

    public function getMinimumPassingLevel() {
        return $this->minimum_passing_level;
    }

    public static function fetchRowByID($eqresponse_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eqresponse_id", "value" => $eqresponse_id, "method" => "=")
        ));
    }

    public static function fetchRowByEQResponseIDEQuestionID($eqresponse_id, $equestion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eqresponse_id", "value" => $eqresponse_id, "method" => "="),
            array("key" => "equestion_id", "value" => $equestion_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "eqresponse_id", "value" => 0, "method" => ">=")));
    }
}