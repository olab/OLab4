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
 * A model for handling the evaluation questions lookup table
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Evaluation_Question extends Models_Base {
    protected $equestion_id, $organisation_id, $question_parent_id, $questiontype_id, $question_code, $question_text, $question_description, $allow_comments, $question_active;

    protected static $table_name = "evaluations_lu_questions";
    protected static $primary_key = "equestion_id";
    protected static $default_sort_column = "question_text";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->equestion_id;
    }

    public function getEquestionID() {
        return $this->equestion_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getQuestionParentID() {
        return $this->question_parent_id;
    }

    public function getQuestiontypeID() {
        return $this->questiontype_id;
    }

    public function getQuestionCode() {
        return $this->question_code;
    }

    public function getQuestionText() {
        return $this->question_text;
    }

    public function getQuestionDescription() {
        return $this->question_description;
    }

    public function getAllowComments() {
        return $this->allow_comments;
    }

    public function getQuestionActive() {
        return $this->question_active;
    }

    public static function fetchRowByID($equestion_id, $question_active = true) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "equestion_id", "value" => $equestion_id, "method" => "="),
            array("key" => "question_active", "value" => $question_active, "method" => "=")
        ));
    }

    public static function fetchAllRecords($question_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "question_active", "value" => $question_active, "method" => "=")));
    }
}