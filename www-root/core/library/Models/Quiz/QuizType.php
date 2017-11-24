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
 * A model to handle quiz types
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Quiz_QuizType extends Models_Base {
    
    protected $quiztype_id, $quiztype_code, $quiztype_title, $quiztype_description, $quiztype_active, $quiztype_order;
    
    protected static $table_name = "quizzes_lu_quiztypes";
    protected static $default_sort_column = "quiztype_order";
    protected static $primary_key = "quiztype_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($questiontype_id, $quiztype_active = 1) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "quiztype_id", "value" => $quiztype_id, "method" => "=", "mode" => "AND"),
                array("key" => "quiztype_active", "value" => $quiztype_active, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($quiztype_active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "quiztype_active",
                "value"     => $quiztype_active,
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
    
    public function getQuiztypeID() {
        return $this->quiztype_id;
    }

    public function getQuiztypeCode() {
        return $this->quiztype_code;
    }

    public function getQuiztypeTitle() {
        return $this->quiztype_title;
    }

    public function getQuiztypeDescription() {
        return $this->quiztype_description;
    }

    public function getQuiztypeActive() {
        return $this->quiztype_active;
    }

    public function getQuiztypeOrder() {
        return $this->quiztype_order;
    }
   
}