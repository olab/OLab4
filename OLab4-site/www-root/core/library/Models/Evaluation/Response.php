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
 * A model for handling the saved responses to evaluations.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Evaluation_Response extends Models_Base {
    protected $eresponse_id, $eprogress_id, $eform_id, $proxy_id, $efquestion_id, $eqresponse_id, $comments, $updated_date, $updated_by;

    protected static $table_name = "evaluation_responses";
    protected static $primary_key = "eresponse_id";
    protected static $default_sort_column = "eprogress_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->eresponse_id;
    }

    public function getEresponseID() {
        return $this->eresponse_id;
    }

    public function getEprogressID() {
        return $this->eprogress_id;
    }

    public function getEformID() {
        return $this->eform_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getEfquestionID() {
        return $this->efquestion_id;
    }

    public function getEqresponseID() {
        return $this->eqresponse_id;
    }

    public function getComments() {
        return $this->comments;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($eresponse_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eresponse_id", "value" => $eresponse_id, "method" => "=")
        ));
    }

    public static function fetchRowByEProgressIDEFQuestionIDProxyID($eprogress_id, $efquestion_id, $proxy_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eprogress_id", "value" => $eprogress_id, "method" => "="),
            array("key" => "efquestion_id", "value" => $efquestion_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "eresponse_id", "value" => 0, "method" => ">=")));
    }
}