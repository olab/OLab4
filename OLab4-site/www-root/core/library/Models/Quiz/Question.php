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
 * A model to handle quiz questions
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Quiz_Question extends Models_Base {
    
    protected $qquestion_id, $quiz_id, $questiontype_id, $question_text, $question_points, $question_order, $qquestion_group_id, $question_active = 1, $randomize_responses, $course_codes;
    
    protected static $table_name = "quiz_questions";
    protected static $default_sort_column = "question_order";
    protected static $primary_key = "qquestion_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($qquestion_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "qquestion_id", "value" => $qquestion_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($quiz_id, $question_active = 1) {
        global $db;
        
        $output = array();

        $query = "SELECT a.* 
                    FROM `quiz_questions` AS a
                    WHERE a.`quiz_id` = ? AND a.`question_active` = ?
                    ORDER BY a.`question_order`";
        $results = $db->GetAll($query, array($quiz_id, $question_active));
        
        if (!empty($results)) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }
    
    public static function fetchAllMCQ($quiz_id, $question_active = 1) {
        global $db;
        
        $output = array();
        
        $query = "SELECT a.*
                    FROM `quiz_questions` AS a
                    WHERE a.`quiz_id` = ?
                    AND a.`question_active` = ?
                    AND a.`questiontype_id` = '1'
                    ORDER BY `question_order` ASC";
        $results = $db->GetAll($query, array($quiz_id, $question_active));
        if ($results) {
            foreach ($results as $result) {
                if (!is_null($result["qquestion_group_id"])) {
                    $output[$result["qquestion_group_id"]][] = new self($result);
                } else {
                    $output[$result["qquestion_id"]][] = new self($result);
                }
            }
        }
        return $output;
    }
    
    public static function fetchNonMCQ($quiz_id, $question_active = 1) {
        global $db;
        
        $output = array();
        
        $query = "SELECT a.*
                    FROM `quiz_questions` AS a
                    WHERE a.`quiz_id` = ?
                    AND a.`question_active` = ?
                    AND a.`questiontype_id` != '1'
                    ORDER BY `question_order` ASC";
        $results = $db->GetAll($query, array($quiz_id, $question_active));
        if ($results) {
            foreach ($results as $result) {
                $output[$result["question_order"]] = new self($result);
            }
        }
        return $output;
    }
    
    public static function fetchGroupedQuestions($quiz_id, $qquestion_group_id, $question_active = 1) {
        global $db;
        
        $output = array();

        $query = "SELECT a.*
                    FROM `quiz_questions` AS a
                    WHERE a.`quiz_id` = ?
                    AND a.`qquestion_group_id` = ?
                    AND a.`question_active` = ?
                    ORDER BY `question_order` ASC";
        $results = $db->GetAll($query, array($quiz_id, $qquestion_group_id, $question_active));
        if (!empty($results)) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }
    
    public static function fetchNextOrder($quiz_id) {
        global $db;
        
        $next_order = 0;
        $query	= "SELECT MAX(`question_order`) AS `next_order` FROM `quiz_questions` WHERE `quiz_id` = ".$db->qstr($quiz_id)." AND `question_active` = '1'";
        $result = $db->getOne($query);
        if ($result) {
            $next_order = $result;
        }
        
        return $next_order;
    }
    
    public function getQquestionID() {
        return $this->qquestion_id;
    }

    public function getQuizID() {
        return $this->quiz_id;
    }

    public function getQuestiontypeID() {
        return $this->questiontype_id;
    }

    public function getQuestionText() {
        return $this->question_text;
    }

    public function getQuestionPoints() {
        return $this->question_points;
    }

    public function getQuestionOrder() {
        return $this->question_order;
    }

    public function getQquestionGroupID() {
        return $this->qquestion_group_id;
    }

    public function getQuestionActive() {
        return $this->question_active;
    }

    public function getRandomizeResponses() {
        return $this->randomize_responses;
    }

    public function getCourseCodes() {
        return $this->course_codes;
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->qquestion_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`qquestion_id` = ".$db->qstr($this->qquestion_id))) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function delete() {
        global $db;
        
        $query = "DELETE FROM `".static::$table_name."` WHERE `qquestion_id` = ?";
        if ($db->Execute($query, $this->qquestion_id)) {
            return true;
        } else {
            return false;
        }
    }
    
}

?>
