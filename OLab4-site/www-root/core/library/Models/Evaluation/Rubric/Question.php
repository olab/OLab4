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
 * A model for handling evaluation rubric questions
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Evaluation_Rubric_Question extends Models_Base {
    protected $efrquestion_id, $erubric_id, $equestion_id, $question_order;

    protected static $table_name = "evaluation_rubric_questions";
    protected static $primary_key = "efrquestion_id";
    protected static $default_sort_column = "question_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->efrquestion_id;
    }

    public function getEfrquestionID() {
        return $this->efrquestion_id;
    }

    public function getErubricID() {
        return $this->erubric_id;
    }

    public function getEquestionID() {
        return $this->equestion_id;
    }

    public function getQuestionOrder() {
        return $this->question_order;
    }

    public static function fetchRowByID($efrquestion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "efrquestion_id", "value" => $efrquestion_id, "method" => "=")
        ));
    }

    public static function fetchRowByEQuestionID($equestion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "equestion_id", "value" => $equestion_id, "method" => "=")
        ));
    }

    public static function fetchAllByERubricID($erubric_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "erubric_id", "value" => $erubric_id, "method" => "=")));
    }
}