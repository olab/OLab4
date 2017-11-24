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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 */

class Models_Exam_Exam_Element extends Models_Base {
    protected $exam_element_id, $exam_id, $element_type, $element_id, $element_text, $group_id, $order, $points, $not_scored, $deleted_date, $updated_date, $updated_by, $exam;

    protected $question_version, $highlight;

    protected static $table_name = "exam_elements";
    protected static $primary_key = "exam_element_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->exam_element_id;
    }

    public function getExamElementID() {
        return $this->exam_element_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getElementType() {
        return $this->element_type;
    }

    public function getElementID() {
        return $this->element_id;
    }

    public function getElementText() {
        return $this->element_text;
    }

    public function getGroupID() {
        return $this->group_id;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getPoints() {
        return $this->points;
    }

    public function getNotScored() {
        return $this->not_scored;
    }

    public function isScored() {
        if ($this->not_scored == "1") {
            return false;
        } else {
            return true;
        }
    }
    
    public function getUpdatedDate(){
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setExamID($exam_id) {
        $this->exam_id = $exam_id;
    }

    public function setElementID($element_id) {
        $this->element_id = $element_id;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function setGroupID($group_id){
        $this->group_id = $group_id;
    }

    public function setUpdatedBy($updated_by){
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date){
        $this->updated_date = $updated_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }
    
    public function getAdjustedPoints() {
        $adjustment = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($this->exam_element_id, $this->exam_id, "update_points");
        if ($adjustment) {
            return (double)$adjustment->getValue();
        } else {
            return (double)$this->points;
        }
    }

    /* @return bool|Models_Exam_Question_Versions */
    public function getQuestionVersion() {
        if ($this->element_type === 'question') {
            if (NULL === $this->question_version) {
                $this->question_version = Models_Exam_Question_Versions::fetchRowByVersionID($this->element_id);
            }
            return $this->question_version;
        }
        return false;
    }

    /**
     * @return bool|Models_Exam_Exam
     */
    public function getExam() {
        if (NULL === $this->exam){
            $this->exam = Models_Exam_Exam::fetchRowByID($this->exam_id);
        }
        return $this->exam;
    }
    
    public static function fetchNextOrder($exam_id) {
        global $db;
        $query = "SELECT MAX(`order`) + 1 AS `next_order` FROM `exam_elements` WHERE `exam_id` = ? AND `deleted_date` IS NULL";
        $result = $db->GetOne($query, array($exam_id));
        return $result ? $result : "0";
    }

    /* @return bool|Models_Exam_Exam_Element */
    public static function fetchRowByID($exam_element_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Exam_Element */
    public static function fetchRowByExamIDElementIDElementType($exam_id, $element_id, $element_type = "question", $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Exam_Element */
    public static function fetchRowByElementIDExamIDGroupIDElementType($element_id, $exam_id, $group_id, $element_type = "question", $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Exam_Element */
    public static function fetchRowByElementIDElementType($element_id, $element_type = "question", $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllByElementIDElementType($element_id, $element_type = "question", $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllByElementIdGroupIdType($element_id, $group_id, $element_type = "question", $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllByExamIDGroupID($exam_id, $group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllByExamIdOrderGreater($exam_id, $order, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "order", "value" => $order, "method" => ">"),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllByExamIDElementType($exam_id, $element_type = "question", $only_scored = true, $deleted_date = NULL) {
        global $db;

        $elements = false;
        $query = "  SELECT * FROM `exam_elements` 
                    WHERE  `exam_id` = ?
                    AND `element_type` = ?";
        $query .= (($only_scored === true) ? " AND (`not_scored` != '1' OR `not_scored` IS NULL)" : "");
        $query .= " AND `deleted_date` ".
                    ($deleted_date ? "<= ". $deleted_date : "IS NULL")."
                    ORDER BY `order` ASC";

        $results = $db->GetAll($query, array($exam_id, $element_type));

        if ($results) {
            foreach ($results as $result) {
                $exam_element = new self($result);
                $elements[] = $exam_element;
            }
        }

        return $elements;
    }

    /**
     * This method selects scored exam elements that are questions and are not of question type "text"
     *
     * @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllScoredQuestionsByExamID($exam_id, $deleted_date = NULL) {
        global $db;

        $output = array();
        $query = "  SELECT a.* FROM `exam_elements` as a
                        LEFT JOIN `exam_question_versions` as b
                            ON a.`element_id` = b.`version_id`
                        LEFT JOIN `exam_lu_questiontypes` as c
                            ON b.`questiontype_id` = c.`questiontype_id`
                    WHERE  a.`exam_id` = ?
                    AND a.`element_type` = 'question' 
                    AND (a.`not_scored` != '1' OR a.`not_scored` IS NULL)
                    AND c.`shortname` != 'question'
                    AND a.`exam_element_id` NOT IN
                    (
                        SELECT `exam_element_id`
                        FROM `exam_adjustments`
                        WHERE `type` IN ('throw_out')
                    )";
        $query .= " AND a.`deleted_date` ".
                    ($deleted_date ? "<= ". $deleted_date : "IS NULL")."
                    ORDER BY a.`order` ASC";

        $results = $db->GetAll($query, array($exam_id));

        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllByGroupID($group_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "group_id", "value" => $group_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllByExamID($exam_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchAllRecords($exam_id = NULL, $deleted_date = NULL) {
        $self = new self();
        
        $params = array(
            array("key" => "exam_element_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if (!is_null($exam_id)) {
            $params[] = array("key" => "exam_id", "value" => $exam_id, "method" => "=");
        }
        
        return $self->fetchAll($params);
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchQuestionElementsByExamIDObjectiveID($exam_id = NULL, $objective_id = NULL, $deleted_date = NULL) {
        global $db;

        $output = array();
        $query = "SELECT * FROM `exam_elements` as a
                    LEFT JOIN `exam_question_versions` as b
                        ON a.`element_id` = b.`version_id`
                    LEFT JOIN `exam_questions` as c
                        ON b.`question_id` = c.`question_id`
                    LEFT JOIN `exam_question_objectives` as d
                        ON c.`question_id` = d.`question_id`
                    WHERE a.`element_type` = 'question'
                    AND a.`exam_id` = ?
                    AND d.`objective_id` = ?";
        $query .= " AND a.`deleted_date` " . ($deleted_date ? "<= ". $deleted_date : "IS NULL");
        $query .= " GROUP BY a.`exam_element_id`";

        $results = $db->GetAll($query, array($exam_id, $objective_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Exam_Exam_Element[] */
    public static function fetchScorableCategoryQuestionElementsByExamIDObjectiveID($exam_id = NULL, $objective_id = NULL, $deleted_date = NULL) {
        global $db;

        $output = array();
        $query = "SELECT * FROM `exam_elements` as a
                    LEFT JOIN `exam_question_versions` as b
                        ON a.`element_id` = b.`version_id`
                    LEFT JOIN `exam_questions` as c
                        ON b.`question_id` = c.`question_id`
                    LEFT JOIN `exam_question_objectives` as d
                        ON c.`question_id` = d.`question_id`
                    WHERE a.`element_type` = 'question'
                    AND a.`exam_id` = ?
                    AND d.`objective_id` = ?
                    AND d.`deleted_date` is NULL
                    AND a.`exam_element_id` NOT IN
                    (
                        SELECT `exam_element_id`
                        FROM `exam_adjustments`
                        WHERE `type` IN ('throw_out')
                    )";
        $query .= " AND a.`deleted_date` " . ($deleted_date ? "<= ". $db->qstr($deleted_date) : "IS NULL");
        $query .= " GROUP BY a.`exam_element_id`";

        $results = $db->GetAll($query, array($exam_id, $objective_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }
    
    public function delete() {
        $this->deleted_date = time();
        $this->updated_date = time();

        return $this->update();
    }

    /**
     * @param int $group_id
     * @return bool
     */
    public static function isGroupIdPosted($group_id) {
        $exam_elements = Models_Exam_Exam_Element::fetchAllByGroupID($group_id);
        if ($exam_elements && is_array($exam_elements) && !empty($exam_elements)) {
            foreach ($exam_elements as $exam_element) {
                if ($exam_element && is_object($exam_element)) {
                    $posted = $exam_element->isExamPosted();
                    if ($posted === true) {
                        return $posted;
                    }
                }
            }
        }
        return false;
    }

    public function isExamPosted() {
        $exam = $this->getExam();;
        if ($exam && is_object($exam)) {
            $posts = Models_Exam_Post::fetchAllByExamID($exam->getID());
            if ($posts && is_array($posts) && !empty($posts)) {
                return true;
            }
        }
        return false;
    }

    public static function buildElementOrder(array $order) {
        $new_order_array = array();
        $changed = array();
        $offset = 0;
        foreach ($order as $element_id => $item) {
            $new_order = (int) $item["new_order"];
            $old_order = (int) $item["old_order"];

            if ($old_order != $new_order) {
                $changed[$element_id] = $new_order;
            } else {
                $new_order_array[$new_order] = $element_id;
            }
        }

        ksort($new_order_array);

        foreach ($changed as $element_id => $order) {
            $insert_order = $order - 1;
            $insert = array($element_id);
            array_splice($new_order_array, $insert_order, 0, $insert);
        }

        ksort($new_order_array);
        return $new_order_array;
    }
}