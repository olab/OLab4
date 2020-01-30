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
 * A model for handling response to an exam post progress
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Progress extends Models_Base {
    const TABLE_NAME = "exam_progress";
    protected $exam_progress_id,
        $post_id,
        $exam_id,
        $proxy_id,
        $progress_value,
        $submission_date,
        $late,
        $exam_score,
        $exam_value,
        $exam_points,
        $menu_open,
        $use_self_timer,
        $self_timer_start,
        $self_timer_length,
        $created_date,
        $created_by,
        $updated_date,
        $updated_by,
        $deleted_date,
        $started_date,
        $post,
        $progress_responses,
        $exam;

    protected static $table_name = "exam_progress";
    protected static $primary_key = "exam_progress_id";
    protected static $default_sort_column = "post_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->exam_progress_id;
    }

    public function getExamProgressID() {
        return $this->exam_progress_id;
    }

    public function getPostID() {
        return $this->post_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getProgressValue() {
        return $this->progress_value;
    }

    public function getSubmissionDate() {
        return $this->submission_date;
    }

    public function getLate() {
        return $this->late;
    }

    public function getExamScore() {
        if (NULL === $this->exam_score) {
            $exam_points = $this->getExamValue();
            $user_points = $this->getExamPoints();

            if (0 === $exam_points) {
                $exam_score = 0;
            } else {
                if ($exam_points != 0) {
                    $exam_score = number_format((100 * $user_points / $exam_points), 2);
                } else {
                    $exam_score = 0;
                }
            }

            $this->exam_score = $exam_score;
        }

        return $this->exam_score;
    }

    public function getExamValue() {
        return $this->exam_value;
    }

    public function getExamPoints() {
        return (float)$this->exam_points;
    }

    public function getMenuOpen() {
        return $this->menu_open;
    }

    public function getUseSelfTimer() {
        return $this->use_self_timer;
    }

    public function getSelfTimerStart() {
        return $this->self_timer_start;
    }

    public function getSelfTimerLength() {
        return $this->self_timer_length;
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

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getStartedDate() {
        return $this->started_date;
    }

    public function setProgressValue($value) {
        $this->progress_value = $value;
    }

    public function setMenuOpen($value) {
        $this->menu_open = $value;
    }

    public function setCreatedDate($value) {
        $this->created_date = $value;
    }

    public function setUpdatedDate($value) {
        $this->updated_date = $value;
    }

    public function setUpdateBy($user) {
        $this->updated_by = $user;
    }

    public function setDeleteDate($value) {
        $this->deleted_date = $value;
    }

    public function setStartedDate($value) {
        $this->started_date = $value;
    }

    public function setSubmissionDate($date) {
        $this->submission_date = $date;
    }

    public function setID($id) {
        $this->exam_progress_id = $id;
    }

    public function setExamValue($value) {
        $this->exam_value = $value;
    }

    public function setExamPoints($value) {
        $this->exam_points = $value;
    }

    public function setLate($minuets) {
        $this->late = $minuets;
    }
    
    public function setUseSelfTimer($use_self_timer) {
        $this->use_self_timer = $use_self_timer;
    }

    public function setSelfTimerStart($self_timer_start) {
        $this->self_timer_start = $self_timer_start;
    }

    public function setSelfTimerLength($self_timer_length) {
        $this->self_timer_length = $self_timer_length;
    }
    
    /**
     * Updates the cumulative score stored in the exam_progress table based on
     * the individual point values in exam_progress_responses.
     * @return boolean
     */
    public function updateScore() {
        $exam_points = 0;
        $user_points = 0;
        $responses = Models_Exam_Progress_Responses::fetchAllByProgressIDNoText($this->getID());
        if (!$responses) {
            return false;
        }
        foreach ($responses as $response) {
            $exam_element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
            if (!$exam_element) {
                return false;
            }
            $exam_points += $exam_element->getAdjustedPoints();
            $user_points += $response->getScore();
        }

        $this->setExamPoints($user_points);
        $this->setExamValue($exam_points);

        return $this->update();
    }

    /**
     * Updates the cumulative score stored in the exam_progress table based on
     * the individual point values in exam_progress_responses.
     * @return boolean
     */
    public function getMissedResponses() {
        $exam_points = 0;
        $user_points = 0;
        $missed_questions = array();
        $responses = Models_Exam_Progress_Responses::fetchAllByProgressIDNoText($this->getID());
        if (!$responses) {
            return false;
        }
        foreach ($responses as $response) {
            $exam_element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
            if (!$exam_element) {
                return false;
            }

            $exam_points        = $exam_element->getAdjustedPoints();
            $response_points    = $response->getScore();
            if ($exam_points != $response_points) {
                $missed_questions[] = $response;
            }
        }

        return $missed_questions;
    }

    public function getExam() {
        if (NULL === $this->exam) {
            $this->exam = Models_Exam_Exam::fetchRowByID($this->exam_id);
        }

        return $this->exam;
    }

    public function getExamPost() {
        if (NULL === $this->post) {
            $this->post = Models_Exam_Post::fetchRowByID($this->post_id);
        }

        return $this->post;
    }

    public function getExamProgressResponses($limit = NULL, $page = 1, $include_page_breaks = false) {
        $this->progress_responses = Models_Exam_Progress_Responses::fetchAllByProgressID($this->getExamProgressID(), $limit, $page, $include_page_breaks);

        return $this->progress_responses;
    }

    public function countExamProgressResponsePageBreaks(){
        return Models_Exam_Progress_Responses::countAllPageBreaksByProgressID($this->getExamProgressID());
    }

    public function countExamProgressResponseQuestions(){
        return Models_Exam_Progress_Responses::countAllQuestionsByProgressID($this->getExamProgressID());
    }

    public function countExamProgressResponseElements($include_page_breaks = false){
        return Models_Exam_Progress_Responses::countAllElementsByProgressID($this->getExamProgressID(), $include_page_breaks);
    }

    public function countProgressResponses($limit = NULL, $page = 1) {
        $count = 0;
        $responses = $this->getExamProgressResponses($limit, $page);
        if (isset($responses) && is_array($responses)) {
            foreach ($responses as $response) {
                if (isset($response) && is_object($response)) {
                    if ($response->getQuestionType() != "text") {
                        //check to make sure response is set
                        $answer_responses = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getID());

                        if (isset($answer_responses) && is_array($answer_responses) && !empty($answer_responses)) {
                            $answer = false;

                            foreach ($answer_responses as $answer_response) {
                                if (isset($answer_response) && is_object($answer_response) && !empty($answer_response)) {
                                    $response_value = $answer_response->getResponseValue();
                                    if (isset($response_value) && $response_value != "") {
                                        $answer = true;
                                    }
                                }
                            }

                            if ($answer === true) {
                                $count++;
                            }
                        }
                    }
                }
            }
        }
        return $count;
    }

    /* @return bool|Models_Exam_Progress */
    public static function fetchRowByID($exam_progress_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Progress */
    public static function fetchRowByPostIDProxyID($post_id, $proxy_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllByPostIDProxyID($post_id, $proxy_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ), "=", "AND", "updated_date", "DESC");
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllByPostIDProxyIDProgressValue($post_id, $proxy_id, $progress_value, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "progress_value", "value" => $progress_value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ), "=", "AND", "submission_date", "ASC");
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllByPostID($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllByPostIDProgressValue($post_id, $progress_value, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "progress_value", "value" => $progress_value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ), "=", "AND", "exam_points");
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllStudentsByPostID($post_id) {
        global $db;

        $output = array();
        $query = "SELECT a.* FROM `".self::TABLE_NAME."` as a
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User::TABLE_NAME . "` as b 
                        ON a.`proxy_id` = b.`id`
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User_Access::TABLE_NAME . "` as c
                        ON b.`id` = c.`user_id`
                    WHERE a.`post_id` = ?
                        AND a.`deleted_date` IS NULL
                        AND c.`group` = 'student'";
        $results = $db->GetAll($query, array($post_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllStudentsByPostIDProgressValue($post_id, $progress_value) {
        global $db;

        $output = array();
        $query = "SELECT a.* FROM `".self::TABLE_NAME."` as a
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User::TABLE_NAME . "` as b 
                        ON a.`proxy_id` = b.`id`
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User_Access::TABLE_NAME . "` as c
                        ON b.`id` = c.`user_id`
                    WHERE a.`post_id` = ?
                        AND a.`progress_value` = ?
                        AND a.`deleted_date` IS NULL
                        AND c.`group` = 'student'
                    ORDER BY a.`exam_points` ASC";
        $results = $db->GetAll($query, array($post_id, $progress_value));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllStudentsByPostIDsProgressValue($post_ids, $progress_value) {
        global $db;

        $output = array();
        $query = "SELECT a.* FROM `".self::TABLE_NAME."` as a
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User::TABLE_NAME . "` as b 
                        ON a.`proxy_id` = b.`id`
                    LEFT JOIN `" . AUTH_DATABASE . "`.`" . Models_User_Access::TABLE_NAME . "` as c
                        ON b.`id` = c.`user_id`
                    WHERE a.`post_id` IN (" . $post_ids . ")
                        AND a.`progress_value` = ?
                        AND a.`deleted_date` IS NULL
                        AND c.`group` = 'student'
                    ORDER BY a.`exam_points` ASC";

        $results = $db->GetAll($query, array($progress_value));

        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_progress_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public static function fetchAllByProgressValue($progress_value, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "progress_value", "value" => $progress_value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ), "=", "AND", "submission_date", "ASC");
    }

    /**
     * Gets the number of the given attempt. The attempt number is the amount of
     * attempts that were created before the given attempt, plus one.
     * 
     * @global ADODB $db
     * @param Models_Exam_Progress $exam_progress
     * @return int
     */
    public static function fetchAttemptNumber(Models_Exam_Progress $exam_progress) {
        global $db;
        $query = "
            SELECT (1 + COUNT(*))
            FROM `exam_progress`
            WHERE `proxy_id` = ".$db->qstr($exam_progress->getProxyId())."
            AND `post_id` = ".$db->qstr($exam_progress->getPostId())."
            AND `created_date` < ".$db->qstr($exam_progress->getCreatedDate());
        return (int)$db->GetOne($query);
    }

    public static function getAttemptCount($progress_array) {
        $attempt_count = 0;
        if (is_array($progress_array)) {
            $attempt_count = count($progress_array);
        }
        return $attempt_count;
    }
}