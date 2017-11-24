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
 * A model for handling response to an exam post progress responses
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Progress_Responses extends Models_Base {
    const TABLE_NAME = "exam_progress_responses";
    protected $exam_progress_response_id,
        $exam_progress_id,
        $exam_id,
        $post_id,
        $proxy_id,
        $exam_element_id,
        $epr_order,
        $question_count,
        $question_type,
        $flag_question,
        $strike_out_answers,
        $grader_comments,
        $learner_comments,
        $mark_faculty_review,
        $comments,
        $score,
        $regrade,
        $graded_by,
        $graded_date,
        $view_date,
        $created_date,
        $created_by,
        $updated_date,
        $updated_by,
        $element,
        $progress,
        $exam,
        $post,
        $exam_element_highlight,
        $question_highlight,
        $question_fnb_highlight;

    protected static $table_name = "exam_progress_responses";
    protected static $primary_key = "exam_progress_response_id";
    protected static $default_sort_column = "exam_progress_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->exam_progress_response_id;
    }

    public function getExamProgressResponseID() {
        return $this->exam_progress_response_id;
    }

    public function getExamProgressID() {
        return $this->exam_progress_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getPostID() {
        return $this->post_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getExamElementID() {
        return $this->exam_element_id;
    }

    public function getOrder() {
        return $this->epr_order;
    }

    public function getQuestionCount() {
        return $this->question_count;
    }

    public function getQuestionType() {
        return $this->question_type;
    }

    public function getFlagQuestion() {
        return $this->flag_question;
    }

    public function getStrikeOutAnswers() {
        return $this->strike_out_answers;
    }

    public function getGraderComments() {
        return $this->grader_comments;
    }

    public function getLearnerComments() {
        return $this->learner_comments;
    }

    public function getMarkFacultyReview() {
        return $this->mark_faculty_review;
    }

    public function getScore() {
        return (float)$this->score;
    }

    public function getRegrade() {
        return $this->regrade;
    }

    public function getGradedBy() {
        return $this->graded_by;
    }

    public function getGradedDate() {
        return $this->graded_date;
    }

    public function getViewDate() {
        return $this->view_date;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdateDate($time) {
        $this->updated_date = $time;
    }

    public function setQuestionType($type) {
        $this->question_type = $type;
    }

    public function setFlagQuestion($value) {
        $this->flag_question = $value;
    }

    public function setScore($score) {
        $this->score = $score;
    }

    public function setStrikeOutAnswers($value) {
        $this->strike_out_answers = $value;
    }

    public function setGraderComments($value) {
        $this->grader_comments = $value;
    }

    public function setLearnerComments($value) {
        $this->learner_comments = $value;
    }

    public function setMarkFacultyReview($value) {
        $this->mark_faculty_review = $value;
    }

    public function setGradedBy($value) {
        $this->graded_by = $value;
    }

    public function setGradedDate($value) {
        $this->graded_date = $value;
    }

    public function setViewDate($value) {
        $this->view_date = $value;
    }

    public function getElement() {
        if (NULL === $this->element) {
            $this->element = Models_Exam_Exam_Element::fetchRowByID($this->exam_element_id);
        }

        return $this->element;
    }

    public function getProgress() {
        if (NULL === $this->progress) {
            $this->progress = Models_Exam_Progress::fetchRowByID($this->exam_progress_id);
        }

        return $this->progress;
    }

    public function getExam() {
        if (NULL === $this->exam) {
            $this->exam = Models_Exam_Exam::fetchRowByID($this->exam_id);
        }

        return $this->exam;
    }

    public function getPost() {
        if (NULL === $this->post) {
            $this->post = Models_Exam_Post::fetchRowByID($this->post_id);
        }

        return $this->post;
    }

    /* @return bool|Models_Exam_Question_Version_Highlight */
    public function getHighlight() {
        if (NULL === $this->question_highlight) {
            $element = $this->getElement();
            $this->question_highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionId($this->exam_progress_id, $this->proxy_id, $element->getElementID(), "question_text");
        }

        return $this->question_highlight;
    }

    /* @return bool|Models_Exam_Exam_Element_Highlight */
    public function getExamElementHighlight($exam_element_id) {
        if (NULL === $this->exam_element_highlight) {
            $this->exam_element_highlight = Models_Exam_Exam_Element_Highlight::fetchRowByProgressIdProxyIdElementId($this->exam_progress_id, $this->proxy_id, $exam_element_id);
        }

        return $this->exam_element_highlight;
    }

    /*
     * @param ArrayObject|Models_Exam_Progress_Responses[] $responses
     * @return bool $return
     */
    public static function checkResponsePageSaved($responses) {
        $saved = false;

        if (isset($responses) && is_array($responses)) {
            foreach ($responses as $response) {
                $saved_response_answers     = array();
                $unsaved_response_answers   = array();
                if (isset($response) && is_object($response)) {
                    $element = $response->getElement();
                    if ($element && is_object($element) && $element->getElementType() != "text") {
                        $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getExamProgressResponseID());
                        if (isset($answers) && is_array($answers) && !empty($answers)) {
                            foreach ($answers as $answer) {
                                //Check that some response answer has been recorded
                                $response_value = $answer->getResponseValue();
                                if (isset($response_value) && $response_value != "")  {
                                    $saved_response_answers[] = $answer;
                                } else {
                                    $unsaved_response_answers[] = $answer;
                                }
                            }
                        }
                        /**
                         * If at least one answer has been saved for the question, consider it saved
                         * otherwise return false
                         */
                        if (empty($saved_response_answers))  {
                            return false;
                        }
                    }
                }
            }
            $saved = true;
        }
        return $saved;
    }

    public function fetchNextResponse() {
        $exam_progress_id   = $this->exam_progress_id;
        $exam_id            = $this->exam_id;
        $post_id            = $this->post_id;
        $proxy_id           = $this->proxy_id;
        $order              = $this->epr_order;
        $order++;
        $number = NULL;
        $next = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDOrder($exam_progress_id, $exam_id, $post_id, $proxy_id, $order);
        if (isset($next) && is_object($next)) {

            $exam_element = $next->getElement();
            if ($exam_element->getElementType() === "page_break") {
                $order = $next->getOrder();
                $order++;
                $next = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDOrder($exam_progress_id, $exam_id, $post_id, $proxy_id, $order);
            }

            if (isset($next) && is_object($next)) {
                return $next;
            } else {
                return NULL;
            }
        } else {
            return NULL;
        }
    }

    /* @return bool|Models_Exam_Progress_Responses */
    public static function fetchRowByID($exam_progress_response_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_progress_response_id", "value" => $exam_progress_response_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Responses[] */
    public static function fetchAllByExamElementID($exam_element_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Responses[] */
    public static function fetchAllByExamElementIDPostID($exam_element_id, $post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Responses[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_progress_response_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Progress_Responses */
    public static function fetchRowByProgressIDElementID($exam_progress_id, $exam_element_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }


    /* @return bool|Models_Exam_Progress_Responses */
    public static function fetchRowByProgressIDExamIDPostIDProxyIDElementID($exam_progress_id, $exam_id, $post_id, $proxy_id, $exam_element_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Progress_Responses */
    public static function fetchRowByProgressIDExamIDPostIDProxyIDOrder($exam_progress_id, $exam_id, $post_id, $proxy_id, $order, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "epr_order", "value" => $order, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Responses[] */
    public static function fetchAllByProgressIDExamIDPostIDProxyIDElementID($exam_progress_id, $exam_id, $post_id, $proxy_id, $exam_element_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress_Responses[] */
    public static function fetchAllByProgressID($exam_progress_id, $limit = NULL, $page = 1, $include_page_breaks = false, $deleted_date = NULL) {
        global $db;
        $responses = false;
        $query = "  SELECT `epr`.`exam_progress_response_id`,
                           `epr`.`exam_progress_id`,
                            `epr`.`exam_id`,
                            `epr`.`post_id`,
                            `epr`.`proxy_id`,
                            `epr`.`exam_element_id`,
                            `epr`.`epr_order`,
                            `epr`.`question_count`,
                            `epr`.`question_type`,
                            `epr`.`flag_question`,
                            `epr`.`strike_out_answers`,
                            `epr`.`grader_comments`,
                            `epr`.`learner_comments`,
                            `epr`.`mark_faculty_review`,
                            `epr`.`score`,
                            `epr`.`regrade`,
                            `epr`.`graded_by`,
                            `epr`.`graded_date`,
                            `epr`.`view_date`,
                            `epr`.`created_date`,
                            `epr`.`created_by`,
                            `epr`.`updated_date`,
                            `epr`.`updated_by`
                    FROM `exam_progress_responses` as `epr`
                    LEFT JOIN `exam_elements` AS `ee`
                        ON `epr`.`exam_element_id` = `ee`.`exam_element_id`
                    WHERE `epr`.`exam_progress_id` = '".$exam_progress_id."'
                    ". ((false === $include_page_breaks) ? "AND `ee`.`element_type` != 'page_break'" : "") . "
                    AND `epr`.`deleted_date` " .($deleted_date ? "<=" : "IS"). " " .($deleted_date ? $deleted_date : "NULL")."
                    ORDER BY `epr`.`epr_order` ASC";

        if (NULL != $limit && $limit >= 1){
            $query      = $query . " LIMIT " . ( ( $page - 1 ) * $limit ) . ", " . $limit;
        }

        $results = $db->GetAll($query);

        if ($results) {
            foreach ($results as $response) {
                $new_self = new self($response);
                if ($new_self && is_object($new_self)) {
                    $responses[] = $new_self;
                }
            }
        }
        return $responses;
    }

    public static function countAllQuestionsByProgressID($exam_progress_id, $deleted_date = NULL){
        global $db;
        $query = "SELECT COUNT(*) as `count` FROM `exam_progress_responses`
                    WHERE `exam_progress_id` = '".$exam_progress_id."'
                    AND `question_count` >= '1'
                    AND `deleted_date` " .($deleted_date ? "<=" : "IS"). " " .($deleted_date ? $deleted_date : "NULL");

        $result = $db->GetRow($query);

        return $result['count'];
    }

    public static function countAllElementsByProgressID($exam_progress_id, $include_page_breaks = false, $deleted_date = NULL){
        global $db;
        $query = "SELECT COUNT(*) as `count` FROM `exam_progress_responses`
                    LEFT JOIN `exam_elements` AS b
                        ON `exam_progress_responses`.`exam_element_id` = b.`exam_element_id`
                    WHERE `exam_progress_responses`.`exam_progress_id` = '".$exam_progress_id."'
                    ". ((false === $include_page_breaks) ? "AND b.`element_type` != 'page_break'" : "") . "
                    AND `exam_progress_responses`.`deleted_date` " .($deleted_date ? "<=" : "IS"). " " .($deleted_date ? $deleted_date : "NULL");

        $result = $db->GetRow($query);

        return $result['count'];
    }

    /**
     * @param $exam_progress_id
     * @param null $deleted_date
     * @return int
     */
    public static function countAllPageBreaksByProgressID($exam_progress_id, $deleted_date = NULL){
        global $db;
        $query = "SELECT COUNT(*) as `count` FROM `exam_progress_responses`
                    LEFT JOIN `exam_elements` AS b
                        ON `exam_progress_responses`.`exam_element_id` = b.`exam_element_id`
                    WHERE `exam_progress_responses`.`exam_progress_id` = '".$exam_progress_id."'
                    AND b.`element_type` != 'page_break'
                    AND `exam_progress_responses`.`deleted_date` " . ($deleted_date ? "<=" : "IS") . " " .($deleted_date ? $deleted_date : "NULL");

        $result = $db->GetRow($query);

        return (int) $result['count'];
    }

    /* @return ArrayObject|Models_Exam_Progress_Responses[] */
    public static function fetchAllByProgressIDNoText($exam_progress_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "question_count", "value" => 1, "method" => ">="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllStudentResponsesByElementIDPostID($element_id, $post_id, $deleted_date = NULL) {
        global $db;

        $output = array();
        $query = "SELECT a.* FROM `".self::TABLE_NAME."` as a
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User::TABLE_NAME . "` as b 
                        ON a.`proxy_id` = b.`id`
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User_Access::TABLE_NAME . "` as c
                        ON b.`id` = c.`user_id`
                    WHERE a.`exam_element_id` = ?
                        AND a.`post_id` = ?
                        AND a.`question_count` >= '1'
                        AND c.`group` = 'student'
                        ";
        $query .= " AND a.`deleted_date` " . ($deleted_date ? "<= ". $db->qstr($deleted_date) : "IS NULL");
        $results = $db->GetAll($query, array($element_id, $post_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }
}