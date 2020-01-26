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
 * A model to handle quiz question types
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Quiz_QuestionType extends Models_Base {

    protected $questiontype_id, $questiontype_title, $questiontype_description, $questiontype_active, $questiontype_order;

    protected static $table_name = "quizzes_lu_questiontypes";
    protected static $default_sort_column = "questiontype_order";
    protected static $primary_key = "questiontype_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($questiontype_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "questiontype_id", "value" => $questiontype_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($questiontype_active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "questiontype_active",
                "value"     => $questiontype_active,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
    
    public function getQuestionTypeID() {
        return $this->questiontype_id;
    }

    public function getQuestionTypeTitle() {
        return $this->questiontype_title;
    }

    public function getQuestionTypeDescription() {
        return $this->questiontype_description;
    }

    public function getQuestionTypeActive() {
        return $this->questiontype_active;
    }

    public function getQuestionTypeOrder() {
        return $this->questiontype_order;
    }

}