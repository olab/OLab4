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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Post extends Models_Base {
    const RESOURCE_TYPE = "exam_post";
    const TABLE_NAME = "exam_posts";
    protected   $post_id,
                $exam_id,
                $target_type,
                $target_id,
                $title,
                $description,
                $instructions,
                $max_attempts,
                $mandatory,
                $backtrack,
                $secure,
                $use_resume_password,
                $resume_password,
                $secure_mode,
                $mark_faculty_review,
                $use_calculator,
                $hide_exam,
                $auto_save,
                $auto_submit,
                $use_time_limit,
                $time_limit,
                $use_self_timer,
                $use_exam_start_date,
                $use_exam_end_date,
                $start_date,
                $end_date,
                $use_exam_submission_date,
                $exam_submission_date,
                $timeframe,
                $grade_book,
                $release_score,
                $use_release_start_date,
                $use_release_end_date,
                $release_start_date,
                $release_end_date,
                $release_feedback,
                $release_incorrect_responses,
                $use_re_attempt_threshold,
                $re_attempt_threshold,
                $re_attempt_threshold_attempts,
                $created_date,
                $created_by,
                $updated_date,
                $updated_by,
                $deleted_date,
                $exam,
                $secure_access_file,
                $secure_access_keys,
                $event,
                $community,
                $exam_exceptions,
                $grade_book_assessment,
                $progress_records,
                $scores,
                $total_points;

    protected static $table_name = "exam_posts";
    protected static $primary_key = "post_id";
    protected static $default_sort_column = "post_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->post_id;
    }

    public function getPostID() {
        return $this->post_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }
    
    public function getTargetType() {
        return $this->target_type;
    }

    public function getTargetID() {
        return $this->target_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getInstructions() {
        return $this->instructions;
    }

    public function getMaxAttempts() {
        return $this->max_attempts;
    }

    public function getMandatory() {
        return $this->mandatory;
    }

    public function getBacktrack() {
        return $this->backtrack;
    }

    public function getSecure() {
        return $this->secure;
    }

    public function getAllowFeedback() {
        return $this->mark_faculty_review;
    }

    public function getUseCalculator() {
        return $this->use_calculator;
    }

    public function getHideExam() {
        return $this->hide_exam;
    }

    public function getAutoSave() {
        return $this->auto_save;
    }

    public function getAutoSubmit() {
        return $this->auto_submit;
    }

    public function getUseTimeLimit() {
        return $this->use_time_limit;
    }

    public function getTimeLimit() {
        return $this->time_limit;
    }

    public function getUseSelfTimer() {
        return $this->use_self_timer;
    }

    public function getUseExamStartDate() {
        return $this->use_exam_start_date;
    }

    public function getUseExamEndDate() {
        return $this->use_exam_end_date;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public function getUseSubmissionDate() {
        return $this->use_exam_submission_date;
    }

    public function getSubmissionDate() {
        return $this->exam_submission_date;
    }

    public function getTimeFrame() {
        return $this->timeframe;
    }

    public function getGradeBook() {
        return $this->grade_book;
    }

    public function getReleaseScore() {
        return $this->release_score;
    }

    //getUseScoreStartDate
    public function getUseReleaseStartDate() {
        return $this->use_release_start_date;
    }

    // getUseScoreEndDate
    public function getUseReleaseEndDate() {
        return $this->use_release_end_date;
    }

    public function getReleaseStartDate() {
        return $this->release_start_date;
    }

    public function getReleaseEndDate() {
        return $this->release_end_date;
    }

    public function getReleaseFeedback() {
        return $this->release_feedback;
    }

    public function getReleaseIncorrectResponses() {
        return $this->release_incorrect_responses;
    }

    public function getUseRAThreshold() {
        return $this->use_re_attempt_threshold;
    }

    public function getRAThreshold() {
        return $this->re_attempt_threshold;
    }

    public function getRAThresholdAttempts() {
        return $this->re_attempt_threshold_attempts;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getAttendanceRequired() {
        // @todo build this into the post for now return false
        return 0;
    }

    public function getQuestionsPerPage() {
        // @todo build this into the post for now return false
        return 0;
    }

    /* @return bool|Models_Exam_Exam */
    public function getExam() {
        if (NULL === $this->exam){
            $this->exam = Models_Exam_Exam::fetchRowByID($this->exam_id);
        }

        return $this->exam;
    }

    public function getExamTotalPoints() {
        if (NULL === $this->exam){
            global $db;

            $query = "  SELECT SUM(a.`points`) as `total_points` FROM `exam_elements` as a
                        LEFT JOIN `exam_question_versions` as b
                            ON a.`element_id` = b.`version_id`
                        LEFT JOIN `exam_lu_questiontypes` as c
                            ON b.`questiontype_id` = c.`questiontype_id`
                    WHERE  a.`exam_id` = ?
                    AND a.`element_type` = 'question' 
                    AND (a.`not_scored` != '1' OR a.`not_scored` IS NULL)
                    AND c.`shortname` != 'question'
                    AND a.`deleted_date` IS NULL";

            $result = $db->GetRow($query, array($this->exam_id));
            if ($result) {
                $this->total_points = $result["total_points"];
            }
        }

        return $this->total_points;
    }

    public function getProgressRecords($include_test_accounts = false) {
        if (NULL === $this->progress_records) {

            $this->progress_records = ($include_test_accounts) ? Models_Exam_Progress::fetchAllByPostID($this->post_id) : Models_Exam_Progress::fetchAllStudentsByPostID($this->post_id);
        }

        return $this->progress_records;
    }

    public function getStandardDeviation()
    {
        $scores = $this->getProgressScores();
        $standard_deviation = 0;
        $post = $this;
        if (!empty($scores)) {
            $standard_deviation =  sqrt(array_sum(array_map(function($x, $mean) use ($post){
                    return pow($x - $mean, 2);
                }, $scores, array_fill(0, count($scores), (array_sum($scores) / count($scores))))) / (count($scores) - 1));
        }

        return $standard_deviation;
    }

    /**
     * @return bool
     */
    private function getProgressScores(){
        if (NULL === $this->scores) {
            $this->scores = array();
            $progress_records = $this->getProgressRecords();
            if (!empty($progress_records)) {
                $scores = array();
                foreach ($progress_records as $progress_record) {
                    $scores[] = $progress_record->getExamPoints();
                }
                sort($scores);
                $this->scores = $scores;
            }
        }

        return $this->scores;
    }

    public function getProgressScoresTotal(){
        return count($this->getProgressRecords());
    }

    public function getProgressScoresMin(){
        $min = 0;
        $progress_scores = $this->getProgressScores();
        if (!empty($progress_scores)){
            $min = min($progress_scores);
        }

        return $min;
    }

    public function getProgressScoresMax(){
        $max = 0;
        $progress_scores = $this->getProgressScores();
        if (!empty($progress_scores)){
            $max = max($progress_scores);
        }

        return $max;
    }

    public function getMean(){
        $scores = $this->getProgressScores();
        $mean = 0;
        if (!empty($scores)){
            $mean = array_sum($scores) / count($scores);
        }

        return $mean;
    }

    public function getMedian(){
        $scores = $this->getProgressScores();
        $median = 0;

        if (!empty($scores)){
            $total_exam_takers = count($scores);
            $mid = floor(($total_exam_takers - 1) / 2);
            $median = ($scores[$mid] + $scores[$mid + 1 - $total_exam_takers % 2]) / 2;
        }

        return $median;
    }

    /* @return bool|Models_Gradebook_Assessment */
    public function getGradeBookAssessment() {
        if (NULL === $this->grade_book_assessment) {
            $this->grade_book_assessment = Models_Gradebook_Assessment::fetchRowByID($this->grade_book);
        }

        return $this->grade_book_assessment;
    }

    /* @return bool|Models_Secure_AccessFiles */
    public function getSecureAccessFile() {
        if (NULL === $this->secure_access_file){
            $this->secure_access_file = Models_Secure_AccessFiles::fetchRowByResourceTypeResourceID(self::RESOURCE_TYPE, $this->post_id);
        }

        return $this->secure_access_file;
    }

    /* @return bool|Models_Secure_AccessKeys */
    public function getSecureAccessKeys() {
        if (NULL === $this->secure_access_keys){
            $this->secure_access_keys = Models_Secure_AccessKeys::fetchAllByResourceTypeResourceID(self::RESOURCE_TYPE, $this->post_id);
        }

        return $this->secure_access_keys;
    }

    public function getUseResumePassword(){
        return $this->use_resume_password;
    }

    public function getResumePassword(){
        return $this->resume_password;
    }

    public function getSecureMode()
    {
        return $this->secure_mode;
    }

    /* @return bool|Models_Event */
    public function getEvent() {
        if ($this->target_type !== "event") {
            return false;
        } else {
            if (NULL === $this->event) {
                $this->event = Models_Event::fetchRowByID($this->target_id);
            }
            return $this->event;
        }
    }

    /* @return bool|Models_Community */
    public function getCommunity() {
        if ($this->target_type !== "community") {
            return false;
        } else {
            if (NULL === $this->community) {
                $this->community = Models_Community::fetchRowByCommunityID($this->target_id);
            }
            return $this->community;
        }
    }

    /* @return ArrayObject|Models_Exam_Post_Exception */
    public function getExamExceptions() {
        if (NULL === $this->exam_exceptions){
            $this->exam_exceptions = Models_Exam_Post_Exception::fetchAllByPostID($this->post_id);
        }

        return $this->exam_exceptions;
    }

    public function setDeletedDate($date) {
        $this->deleted_date = $date;
    }

    public function setGradeBook($id) {
        $this->grade_book = $id;
    }

    public function setUpdatedDate($date) {
        $this->updated_date = $date;
    }

    public function setUpdatedBy($id) {
        $this->updated_by = $id;
    }

    public function setHidden($hide_exam) {
        $this->hide_exam = $hide_exam;
    }


    /* @return bool|Models_Exam_Post */
    public static function fetchRowByID($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Post */
    public static function fetchRowByExamIDNoPreview($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "target_type", "value" => "preview", "method" => "!="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllByEventID($event_id, $deleted_date = NULL) {
        global $ENTRADA_USER;

        $self = new self();
        $array = $self->fetchAll(array(
            array("key" => "target_type", "value" => "event", "method" => "="),
            array("key" => "target_id", "value" => $event_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));

        if (isset($array) && is_array($array)) {
            foreach ($array as $key => $post) {
                if (isset($post) && is_object($post)) {
                    $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());
                    if (isset($exception) && is_object($exception)) {
                        if ($exception->getExcluded() == 1) {
                            //remove post from the array
                            unset($array[$key]);
                        }
                    }
                }
            }
        }

        return $array;
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllByEventIDNotHidden($event_id, $deleted_date = NULL) {
        global $ENTRADA_USER;

        $self = new self();
        $array = $self->fetchAll(array(
            array("key" => "target_type", "value" => "event", "method" => "="),
            array("key" => "target_id", "value" => $event_id, "method" => "="),
            array("key" => "hide_exam", "value" => 0, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));

        if (isset($array) && is_array($array)) {
            foreach ($array as $key => $post) {
                if (isset($post) && is_object($post)) {
                    $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());
                    if (isset($exception) && is_object($exception)) {
                        if ($exception->getExcluded() == 1) {
                            //remove post from the array
                            unset($array[$key]);
                        }
                    }
                }
            }
        }

        return $array;
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllByExamID($exam_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllByExamIDNoPreview($exam_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "target_type", "value" => "preview", "method" => "!="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Post */
    public static function fetchRowByExamIDType($exam_id, $target_type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "target_type", "value" => $target_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllByExamIDType($exam_id, $target_type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "target_type", "value" => $target_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllByGradeBookAssessmentID($assessment_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "grade_book", "value" => $assessment_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllByCommunityID($community_id, $deleted_date = NULL) {
        global $ENTRADA_USER;

        $self = new self();
        $array = $self->fetchAll(array(
            array("key" => "target_type", "value" => "community", "method" => "="),
            array("key" => "target_id", "value" => $community_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));

        if (isset($array) && is_array($array)) {
            foreach ($array as $key => $post) {
                if (isset($post) && is_object($post)) {
                    $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());
                    if (isset($exception) && is_object($exception)) {
                        if ($exception->getExcluded() == 1) {
                            //remove post from the array
                            unset($array[$key]);
                        }
                    }
                }
            }
        }

        return $array;
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchFilteredPosts($search_value = "", $filters = array(), $offset = 0, $limit = 50) {
        global $db, $ENTRADA_USER;
        $posts = false;
        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        
        $query = "  SELECT a.`post_id`, a.`title`, a.`course_id`, a.`updated_date` FROM `exam_posts` AS a";
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_post_authors` AS b
                            ON a.`post_id` = b.`post_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_post_authors` AS c
                            ON a.`post_id` = b.`post_id`
                            AND c.`author_type` = 'organisation_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["organisation"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `exam_post_authors` AS d
                            ON a.`post_id` = b.`post_id`
                            AND d.`author_type` = 'course_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }
        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= " JOIN `exam_post_authors` AS b
                            ON a.`post_id` = b.`post_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (b.`author_type` = 'organisation_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            }
        }
        
        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }
            
            if (array_key_exists("organisation", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }
            
            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }
        }
        
        $query .= " GROUP BY a.`post_id`
                    ORDER BY a.`updated_date`
                    LIMIT " . (int) $offset . ", " . (int) $limit;
        
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $post) {
                $posts[] = new self($post);
            }
        }
        
        return $posts;
    }
    
    public static function countAllPosts ($search_value = "", $filters = array()) {
        global $db, $ENTRADA_USER;
        $total_posts = 0;
        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        
        $query = "  SELECT COUNT(a.`post_id`) AS `total_posts` FROM `exam_posts` AS a";
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `exam_post_authors` AS b
                            ON a.`post_id` = b.`post_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= " JOIN `exam_post_authors` AS c
                            ON a.`post_id` = b.`post_id`
                            AND c.`author_type` = 'organisation_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["organisation"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `exam_post_authors` AS d
                            ON a.`post_id` = b.`post_id`
                            AND d.`author_type` = 'course_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }
        } else {
            if ($ENTRADA_USER->getActiveGroup() != "medtech") {
                $query .= " JOIN `exam_post_authors` AS b
                            ON a.`post_id` = b.`post_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["director"]) . ")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["pcoordinator"]) . ")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (" . implode(",", $course_permissions["ccoordinator"]) . ")) OR" : "") . "
                                (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ") OR
                                (b.`author_type` = 'organisation_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . ")
                            )
                            AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
            }
        }
        
        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }
            
            if (array_key_exists("organisation", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }
            
            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }
        }
        
        $results = $db->GetRow($query);
        if ($results) {
            $total_posts = $results["total_posts"];
        }
        
        return $total_posts;
    }
    
    public static function saveFilterPreferences($filters = array()) {
        global $db;
        
        if (!empty($filters)) {
            foreach ($filters as $filter_type => $filter_targets) {
                foreach ($filter_targets as $target) {
                    $target_label = "";
                    $target = clean_input($target, array("int"));
                    switch ($filter_type) {
                        case "course" :
                            $course = Models_Course::get($target);
                            if ($course) {
                                $target_label = $course->getCourseName();
                            }
                        break;
                        case "author" :
                            $query = "SELECT CONCAT(`firstname`, ' ', `lastname`) AS fullname FROM `". AUTH_DATABASE ."`.`user_data` WHERE `id` = ?";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["fullname"];
                            }
                        break;
                        case "organisation" :
                            $query = "SELECT * FROM `". AUTH_DATABASE ."`.`organisations` WHERE `organisation_id` = ?";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["organisation_title"];
                            }
                        break;
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["exams"]["posts"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }

    public static function fetchAllCourseCoordinators ($search_value = null, $active = 1) {
        global $db;
        $pcoordinators = false;
        
        $query = "  SELECT a.`course_name`, a.`course_code`, b.`contact_id`, c.`id`, c.`firstname`, c.`lastname` FROM `courses` AS a
                    JOIN `course_contacts` AS b
                    ON a.`course_id` = b.`course_id`
                    JOIN `". AUTH_DATABASE ."`.`user_data` AS c
                    ON b.`proxy_id` = c.`id` 
                    WHERE a.`course_active` = ?
                    AND b.`contact_type` = 'pcoordinator'
                    AND (
                        c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .") 
                        OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )";
        
        $results = $db->GetAll($query, array($active));
        if ($results) {
            foreach ($results as $result) {
                $pcoordinators[] = array("proxy_id" => $result["id"], "firstname" => $result["firstname"], "lastname" => $result["lastname"]);
            }
        }
        
        return $pcoordinators;
    }

    /* @return bool|Models_Exam_Exam */
    public function fetchExam() {
        return Models_Exam_Exam::fetchRowByID($this->exam_id);
    }

    /* @return ArrayObject|Models_Exam_Progress[] */
    public function fetchExamProgress($user_id) {
        return Models_Exam_Progress::fetchAllByPostIDProxyID($this->post_id, $user_id);
    }

    public function delete() {
        $this->deleted_date = time();
        $this->updated_date = time();

        return $this->update();
    }

    public static function getExamPostDescription($required = 0, $quiztype_code = "delayed", $quiz_timeout = 0, $quiz_questions = 1, $quiz_attempts = 0, $timeframe = "", $attendance = 0, $course_id = 0) {
        global $db, $RESOURCE_TIMEFRAMES;

        $output    = "This is %s exam to be completed %s. You will have %s and %s to answer the %s in this exam, and your results will be presented %s.%s";

        $string_1 = (((int) $required) ? "a required" : "an optional");
        $string_2 = ((($timeframe) && ($timeframe != "none")) ? strtolower($RESOURCE_TIMEFRAMES["event"][$timeframe]) : "when you see fit");
        $string_3 = (((int) $quiz_timeout) ? $quiz_timeout." minute".(($quiz_timeout != 1) ? "s" :"") : "no time limitation");
        $string_4 = (((int) $quiz_attempts) ? $quiz_attempts." attempt".(($quiz_attempts != 1) ? "s" : "") : "unlimited attempts");
        $string_5 = $quiz_questions." question".(($quiz_questions != 1) ? "s" : "");
        $string_6 = (($quiztype_code == "hide") ? "by a teacher, likely through ".($course_id ? "the <a href=\"".ENTRADA_URL."/profile/gradebook?section=view&id=".$course_id."\"><strong>Course Gradebook</strong></a>" : "a <a href=\"".ENTRADA_URL."/profile/gradebook\"><strong>Course Gradebook</strong></a>") : (($quiztype_code == "delayed") ? "only after the quiz expires" : "immediately after completion"));
        $string_7 = (isset($attendance) && $attendance)?"<br /><br /> This exam requires your attendance. You will not be able to access it if you have not been marked present.":"";
        return sprintf($output, $string_1, $string_2, $string_3, $string_4, $string_5, $string_6, $string_7);
    }


    /*
     *
     * This function gets the course a post is associated with
     * @return bool|Models_Course
     */
    public function getCourse() {
        $target_id = $this->target_id;
        switch ($this->target_type) {
            case "event" :
                $event = Models_Event::fetchRowByID($target_id);
                if (isset($event) && is_object($event)) {
                    $course = Models_Course::get($event->getCourseID());
                }
                break;
            case "community" :
                $community = Models_Community::fetchRowByCommunityID($target_id);
                if (isset($community) && is_object($community)) {
                    $course_community = Models_Community_Course::fetchRowByCommunityID($community->getCourseID());
                    if (isset($course_community) && is_object($course_community)) {
                        $course = Models_Course::get($course_community->getCourseID());
                    }
                }
                break;
        }

        if (isset($course) && is_object($course)) {
            return $course;
        } else {
            return false;
        }
    }


    public function getSubmissionDateException(User $user) {
        $sub = $this->getSubmissionDate();
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->post_id, $user->getID());
        if (isset($exception) && is_object($exception)) {
            if ($exception->getSubmissionDate()) {
                $sub = $exception->getSubmissionDate();
            }
        }

        return $sub;
    }

    public function getUseSubmissionDateException(User $user) {
        $sub = $this->getUseSubmissionDate();
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->post_id, $user->getID());
        if (isset($exception) && is_object($exception)) {
            if ($exception->getUseSubmissionDate() == "1") {
                $sub = $exception->getUseSubmissionDate();
            }
        }

        return $sub;
    }

    public function isScoreStartValid() {
        $time_valid = 0;
        if ($this->getReleaseScore() == 1) {
            $use_date = $this->getUseReleaseStartDate();
            if ($use_date) {
                $date = (int) $this->getReleaseStartDate();

                if (isset($date) && $date != 0 && $date <= time()) {
                    $time_valid = 1;
                } else if (isset($date) && $date == 0) {
                    $time_valid = 1;
                }
            } else {
                $time_valid = 1;
            }
        }
        return $time_valid;
    }

    public function isScoreEndValid() {
        $time_valid = 0;
        if ($this->getReleaseScore() == 1) {
            $use_date = $this->getUseReleaseEndDate();
            if ($use_date) {
                $date = (int) $this->getReleaseEndDate();

                if (isset($date) && $date != 0 && $date >= time()) {
                    $time_valid = 1;
                } else if (isset($date) && $date == 0) {
                    $time_valid = 1;
                }
            } else {
                $time_valid = 1;
            }
        }
        return $time_valid;
    }

    public function isFeedbackStartValid() {
        $time_valid = 0;
        if ($this->getReleaseFeedback() == 1 && $this->getReleaseScore() == 1) {
            $use_date = $this->getUseReleaseStartDate();
            if ($use_date) {
                $date = (int) $this->getReleaseStartDate();

                if (isset($date) && $date != 0 && $date <= time()) {
                    $time_valid = 1;
                } else if (isset($date) && $date == 0) {
                    $time_valid = 1;
                }
            } else {
                $time_valid = 1;
            }
        }
        return $time_valid;
    }

    public function isFeedbackEndValid() {
        $time_valid = 0;
        if ($this->getReleaseFeedback() == 1 && $this->getReleaseScore() == 1) {
            $use_date = $this->getUseReleaseEndDate();
            if ($use_date) {
                $date = (int) $this->getReleaseEndDate();

                if (isset($date) && $date != 0 && $date >= time()) {
                    $time_valid = 1;
                } else if (isset($date) && $date == 0) {
                    $time_valid = 1;
                }
            } else {
                $time_valid = 1;
            }
        }
        return $time_valid;
    }

    public function isIncorrectStartValid() {
        $time_valid = 0;
        if ($this->getReleaseIncorrectResponses() == 1 && $this->getReleaseScore() == 1) {
            $use_date = $this->getUseReleaseStartDate();
            if ($use_date) {
                $date = (int) $this->getReleaseStartDate();

                if (isset($date) && $date != 0 && $date <= time()) {
                    $time_valid = 1;
                } else if (isset($date) && $date == 0) {
                    $time_valid = 1;
                }
            } else {
                $time_valid = 1;
            }
        }
        return $time_valid;
    }

    public function isIncorrectEndValid() {
        $time_valid = 0;
        if ($this->getReleaseIncorrectResponses() == 1 && $this->getReleaseScore() == 1) {
            $use_date = $this->getUseReleaseEndDate();
            if ($use_date) {
                $date = (int) $this->getReleaseEndDate();

                if (isset($date) && $date != 0 && $date >= time()) {
                    $time_valid = 1;
                } else if (isset($date) && $date == 0) {
                    $time_valid = 1;
                }
            } else {
                $time_valid = 1;
            }
        }
        return $time_valid;
    }

    public function generateScoreMessage($return_available = 1) {
        $release_score          = $this->getReleaseScore();
        $score_start_valid      = $this->isScoreStartValid();
        $score_end_valid        = $this->isScoreEndValid();
        $start_date             = $this->getReleaseStartDate();
        $end_date               = $this->getReleaseEndDate();
        $use_start_date         = $this->getUseReleaseStartDate();
        $use_end_date           = $this->getUseReleaseEndDate();

        $message = false;
        if ($release_score) {
            if ($score_start_valid) {
                $message = "Exam scores are available to review";
            } else {
                if ($start_date != 0 && $use_start_date) {
                    // exam score hasn't been released
                    $message = "Exam scores will be released on " . date("m-d-Y", $start_date) . " at " . date("g:i a", $start_date) . ".";
                }
            }

            if ($score_end_valid) {
                if ($end_date != 0 && $use_end_date) {
                    $message .= " until " . date("m-d-Y", $end_date) . " at " . date("g:i a", $end_date);
                } else {
                    if (!$return_available && $score_start_valid) {
                        $message = false;
                    } else {
                        $message .= ".";
                    }
                }
            } else {
                if ($use_end_date) {
                    $message = "Exam scores was closed on " . date("m-d-Y", $end_date) . " at " . date("g:i a", $end_date) . ".";
                } else {
                    $message = "Exam scores are not scheduled for release.";
                }
            }
        } else {
            $message = "Exam scores are not scheduled for release.";
        }
        return $message;
    }

    public function generateFeedbackMessage($return_available = 1) {
        $release_score          = $this->getReleaseScore();
        $release_feedback       = $this->getReleaseFeedback();
        $feedback_start_valid   = $this->isFeedbackStartValid();
        $feedback_end_valid     = $this->isFeedbackEndValid();
        $start_date             = $this->getReleaseStartDate();
        $end_date               = $this->getReleaseEndDate();
        $use_start_date         = $this->getUseReleaseStartDate();
        $use_end_date           = $this->getUseReleaseEndDate();

        $message = false;
        if ($release_feedback && $release_score) {
            if ($feedback_start_valid) {
                $message = "Exam feedback is available to review";
            } else {
                if ($start_date != 0 && $use_start_date) {
                    // exam score hasn't been released
                    $message = "Exam feedback will be released on " . date("m-d-Y", $start_date) . " at " . date("g:i a", $start_date);
                }
            }

            if ($feedback_end_valid) {
                if ($end_date != 0 && $use_end_date) {
                    $message .= " until " . date("m-d-Y", $end_date) . " at " . date("g:i a", $end_date) . ".";
                } else {
                    if (!$return_available && $feedback_start_valid) {
                        $message = false;
                    } else {
                        $message .= ".";
                    }
                }
            } else {
                if ($use_end_date) {
                    $message = "Exam feedback was closed on " . date("m-d-Y", $end_date) . " at " . date("g:i a", $end_date) . ".";
                } else {
                    $message = "Exam feedback is not scheduled for release.";
                }
            }
        } else {
            $message = "Exam feedback is not scheduled for release.";
        }
        return $message;
    }

    public function generateIncorrectFeedbackMessage($return_available = 1) {
        $release_score          = $this->getReleaseScore();
        $release_incorrect      = $this->getReleaseIncorrectResponses();
        $start_valid            = $this->isIncorrectStartValid();
        $end_valid              = $this->isIncorrectEndValid();
        $start_date             = $this->getReleaseStartDate();
        $end_date               = $this->getReleaseEndDate();
        $use_start_date         = $this->getUseReleaseStartDate();
        $use_end_date           = $this->getUseReleaseEndDate();

        $message = false;
        if ($release_incorrect && $release_score) {
            if ($start_valid) {
                $message = "Exam incorrect feedback is available to review";
            } else {
                if ($start_date != 0 && $use_start_date) {
                    // exam score hasn't been released
                    $message = "Exam incorrect feedback will be released on " . date("m-d-Y", $start_date) . " at " . date("g:i a", $start_date);
                }
            }

            if ($end_valid) {
                if ($end_date != 0 && $use_end_date) {
                    $message .= " until " . date("m-d-Y", $end_date) . " at " . date("g:i a", $end_date) . ".";
                } else {
                    if (!$return_available && $start_valid) {
                        $message = false;
                    } else {
                        $message .= ".";
                    }
                }
            } else {
                if ($use_end_date) {
                    $message = "Exam incorrect feedback was closed on " . date("m-d-Y", $end_date) . " at " . date("g:i a", $end_date) . ".";
                } else {
                    $message = "Exam incorrect feedback is not scheduled for release.";
                }
            }
        } else {
            $message = "Exam incorrect feedback not scheduled for release.";
        }
        return $message;
    }


    /**
     * @deprecated Deprecated in favor of using boolean helper methods isScoreStartValid(), isFeedbackEndValid(), etc.
     * @param $progress Models_Exam_Progress
     * @return array
     */
    public function check_review_dates(Models_Exam_Progress $progress) {
        $review = array();
        if ($this->getReleaseScore() == 1) {
            $review["release_score"] = 1;
            $post_score_start = $this->getReleaseStartDate();
            $post_score_end   = $this->getReleaseEndDate();

            if (isset($post_score_start) && $post_score_start != 0 && $post_score_start <= time()) {
                $review["score_start"] = 1;
                $review["score_start_time"] = $post_score_start;
            } else if (isset($post_score_start) && $post_score_start != 0 && $post_score_start >= time()) {
                $review["score_start"] = 0;
                $review["score_start_time"] = $post_score_start;
            } else if (isset($post_score_start) && $post_score_start == 0) {
                $review["score_start"] = 1;
                $review["score_start_time"] = 0;
            } else {
                $review["score_start"] = 0;
                $review["score_start_time"] = 0;
            }

            if (isset($post_score_end) && $post_score_end != 0 && $post_score_end >= time()) {
                $review["score_end"] = 1;
                $review["score_end_time"] = $post_score_end;
            } else if (isset($post_score_end) && $post_score_end != 0 && $post_score_end <= time()) {
                $review["score_end"] = 0;
                $review["score_end_time"] = $post_score_end;
            } else if (isset($post_score_end) && $post_score_end == 0) {
                $review["score_end"] = 1;
                $review["score_end_time"] = 0;
            } else {
                $review["score_end"] = 0;
                $review["score_end_time"] = 0;
            }
        }

        if ($this->getReleaseFeedback() == 1) {
            $review["release_feedback"] = 1;
            $post_feedback_start = $this->getReleaseStartDate();
            $post_feedback_end   = $this->getReleaseEndDate();

            if (isset($post_feedback_start) && $post_feedback_start != 0 && $post_feedback_start <= time()) {
                $review["feedback_start"] = 1;
                $review["feedback_start_time"] = $post_feedback_start;
            } else if (isset($post_feedback_start) && $post_feedback_start != 0 && $post_feedback_start >= time())  {
                $review["feedback_start"] = 0;
                $review["feedback_start_time"] = $post_feedback_start;
            } else if (isset($post_feedback_start) && $post_feedback_start == 0) {
                $review["feedback_start"] = 1;
                $review["feedback_start_time"] = 0;
            } else {
                $review["feedback_start"] = 0;
            }

            if (isset($post_feedback_end) && $post_feedback_end != 0 && $post_feedback_end >= time()) {
                $review["feedback_end"] = 1;
                $review["feedback_end_time"] = $post_feedback_end;
            } else if (isset($post_feedback_end) && $post_feedback_end != 0 && $post_feedback_end <= time()) {
                $review["feedback_end"] = 0;
                $review["feedback_end_time"] = $post_feedback_end;
            } else if (isset($post_feedback_end) && $post_feedback_end == 0) {
                $review["feedback_end"] = 1;
                $review["feedback_end_time"] = 0;
            } else {
                $review["feedback_end"] = 0;
                $review["feedback_end_time"] = 0;
            }
        }

        $url = ENTRADA_RELATIVE . "/exams?section=feedback&progress_id=" . $progress->getID();

        if ($review["score_start"] == 1) {
            $score_message = "Exam scores are available to review";
        } else if ($review["score_start_time"] != 0) {
            //exam score hasn't been released
            $score_message = "Exam scores will be released on " . date("m-d-Y", $review["score_start_time"]) . " at " . date("g:i a", $review["score_start_time"]);;
        }

        if ($review["score_end"] == 1) {
            if ($review["score_end_time"] != 0) {
                $score_message .= " until " . date("m-d-Y", $review["score_end_time"]) . " at " . date("g:i a", $review["score_end_time"]);;
            } else {
                $score_message .= ".";
            }
        } else if ($review["score_end_time"] != 0) {
            $score_message = "Exam scores was closed on " . date("m-d-Y", $review["score_end_time"]) . " at " . date("g:i a", $review["score_end_time"]);;
        }

        if ($review["feedback_start"] == 1) {
            $feedback_message = "Exam feedback is available to review";
        } else if ($review["feedback_start_time"] != 0) {
            //exam score hasn't been released
            $feedback_message = "Exam feedback will be released on " . date("m-d-Y", $review["feedback_start_time"]) . " at " . date("g:i a", $review["feedback_start_time"]);;
        }

        if ($review["feedback_end"] == 1) {
            if ($review["feedback_end_time"] != 0) {
                $feedback_message .= " until " . date("m-d-Y", $review["feedback_end_time"]) . " at " . date("g:i a", $review["feedback_end_time"]);;
            } else {
                $feedback_message .= ".";
            }
        } else if ($review["feedback_end_time"] != 0) {
            $feedback_message = "Exam feedback was closed on " . date("m-d-Y", $review["feedback_end_time"]) . " at " . date("g:i a", $review["feedback_end_time"]);;
        }

        if ($review["score_start"] == 1 && $review["score_end"] == 1) {
            $score = 1;
        } else {
            $score = 0;
        }

        if ($review["feedback_start"] == 1 && $review["feedback_end"] == 1) {
            $feedback = 1;
        } else {
            $feedback = 0;
        }

        if ($review["score_start"] == 1 && $review["score_end"] == 1 || $review["feedback_start"] == 1 && $review["feedback_end"] == 1) {
            $href = $url;
        }

        $return_array = array();

        $return_array["release_score"]           = $review["release_score"];
        $return_array["release_score_access"]    = $score;
        $return_array["release_feedback"]        = $review["release_feedback"];
        $return_array["release_feedback_access"] = $feedback;
        $return_array["score_message"]          = $score_message;
        $return_array["feedback_message"]       = $feedback_message;
        $return_array["href"]                   = $href;

        return $return_array;
    }

    public function check_event_audience($ENTRADA_USER) {
        $post       = $this;
        $event_id   = $post->getTargetID();
        $event      = Models_Event::fetchRowByID($event_id);

        $event_audiences = Models_Event_Audience::fetchAllByEventID($event_id);
        $course_contacts = Models_Course_Contact::fetchAllByCourseID($event->getCourseID());
        $access_audience = false;

        $course_contact_members = array();
        //check if user is in audience for target post or is in course contacts or medtech admin
        if (isset($course_contacts) && is_array($course_contacts) && !empty($course_contacts)) {
            foreach ($course_contacts as $course_contact) {
                $course_contact_array = $course_contact->toArray();
                if (!in_array($course_contact_array["proxy_id"], $course_contact_members)) {
                    $course_contact_members[] = $course_contact_array["proxy_id"];
                }
            }
        }

        if (isset($event_audiences) && is_array($event_audiences) && !empty($event_audiences)) {
            foreach ($event_audiences as $event_audience) {
                if (isset($event_audience) && is_object($event_audience)) {
                    $event_audience_obj = $event_audience->getAudience($event->getEventStart());
                    $audience[] = $event_audience_obj;
                }
            }

            if (is_array($audience) && !empty($audience)) {
                $audience_members = Models_Event_Audience::buildAudienceMembers($audience);
            }

            if (in_array($ENTRADA_USER->getID(), $audience_members) || in_array($ENTRADA_USER->getID(), $course_contact_members) || $ENTRADA_USER->getActiveRole() == "admin") {
                $access_audience = true;
            }

        } else {
            //no audience for the event
        }

        //check to see if the user is excluded
        $excluded = Models_Exam_Post_Exception::fetchRowByPostIdProxyIdExcluded($post->getID(), $ENTRADA_USER->getID());
        if (isset($excluded) && is_object($excluded) && !empty($excluded)) {
            $access_audience = false;
        }

        return $access_audience;
    }


    public function getAudience() {
        $post       = $this;
        $event_id   = $post->getTargetID();
        $event      = Models_Event::fetchRowByID($event_id);
        $audience_members = array();

        $event_audiences = Models_Event_Audience::fetchAllByEventID($event_id);

        if (isset($event_audiences) && is_array($event_audiences) && !empty($event_audiences)) {
            foreach ($event_audiences as $event_audience) {
                $event_audience_obj = $event_audience->getAudience($event->getEventStart());
                $audience[] = $event_audience_obj;
            }

            if (is_array($audience) && !empty($audience)) {
                $audience_members = Models_Event_Audience::buildAudienceMembers($audience);
            }

            $exceptions = Models_Exam_Post_Exception::fetchAllByPostIDExcluded($post->getID());
            if (isset($exceptions) && is_array($exceptions)) {
                foreach ($exceptions as $exception) {
                    $index = array_search($exception->getProxyID(), $audience_members);
                    if (array_key_exists($index, $audience_members)) {
                        unset($audience_members[$index]);
                    }
                }
            }
        }

        return $audience_members;
    }

    /**
     * Determine whether the user is able to start a new exam attempt
     *
     * A new attempt is allowed if the time() is after the start_date
     * and before the end_date or one has not be set
     *
     * @param User $user
     * @uses Models_Exam_Post_Exception::fetchRowByPostIdProxyId() to retrieve exceptions
     * @return bool
     */
    public function isNewAttemptAllowedByUser(User $user) {
        $max_attempts           = (int)$this->getMaxAttempts();
        $start_date             = (int)$this->start_date;
        $end_date               = (int)$this->end_date;
        $use_exam_start_date    = $this->use_exam_start_date;
        $use_exam_end_date      = $this->use_exam_end_date;
        $start_valid            = false;
        $end_valid              = false;
        $attempt_allowed        = false;
        $current_attempt        = 1;

        //Check Exceptions
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->getID(), $user->getID());
        if (isset($exception) && is_object($exception)) {
            if ($exception->getUseStartDate() == "1") {
                $exc_date_start = $exception->getStartDate();
                $start_date = $exc_date_start;
            }

            if ($exception->getUseEndDate() == "1") {
                $exc_date_end = $exception->getEndDate();
                $end_date = $exc_date_end;
            }

            if ($exception->getAttempts()) {
                $max_attempts = $exception->getAttempts();
            }
            
            if ($exception->getExcluded() == 1) {
                return false;
            }
        }

        $progress_attempts = Models_Exam_Progress::fetchAllByPostIDProxyID($this->getID(), $user->getID());

        if ($progress_attempts && is_array($progress_attempts)) {
            $current_attempt = count($progress_attempts) + 1;
        }

        if ($current_attempt <= $max_attempts) {
            $attempt_allowed = true;
        }

        if (null !== $start_date) {
            if ($start_date <= time() || $start_date == 0 || $use_exam_start_date == 0) {
                $start_valid = true;
            }
        } else {
            $start_valid = true;
        }

        if (null !== $end_date) {
            if ($end_date >= time() || $end_date == 0 || $use_exam_end_date == 0) {
                $end_valid = true;
            }
        } else {
            $end_valid = true;
        }

        if ($start_valid === true && $end_valid === true && $attempt_allowed === true) {
            return true;
        }

        return false;
    }

    /**
     * Determine if a user is allowed to resume an exam attempt
     *
     * This is currently the same logic as isSubmitAttemptAllowed() since a user is allowed to resume or submit an attempt
     * IF they started the exam between the exam start/end time
     * AND the submission deadline has not passed OR has not been set
     *
     * @param User $user
     * @uses Models_Exam_Post_Exception::fetchRowByPostIdProxyId() to retrieve exceptions
     * @return bool
     */
    public function isResumeAttemptAllowedByUser(User $user) {
        $sub_date   = ($this->use_exam_submission_date == "1") ? $this->exam_submission_date : false;

        //Check Exceptions
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->getID(), $user->getID());
        if (isset($exception) && is_object($exception)) {
            if ($exception->getUseSubmissionDate() == "1") {
                $exc_date_sub = $exception->getSubmissionDate();
                $sub_date = $exc_date_sub;
            }

            if ($exception->getExcluded() == 1) {
                return false;
            }
        } else {
            $use_sub_date = $this->use_exam_submission_date;
        }

        if ($sub_date && $use_sub_date) {
            if ($sub_date >= time() || $sub_date == 0) {
                $submission_valid = true;
            }
        } else {
            $submission_valid = true;
        }

        if ($submission_valid) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether user is allowed to submit an exam attempt
     *
     * This is currently the same logic as isResumeAttemptAllowed() since a user is allowed to resume or submit an attempt
     * IF they started the exam between the exam start/end time
     * AND the submission deadline has not passed OR has not been set
     *
     * @param User $user
     * @uses Models_Exam_Post_Exception::fetchRowByPostIdProxyId() to retrieve exceptions
     * @return bool
     */
    public function isSubmitAttemptAllowedByUser(User $user) {
        $sub_date   = ($this->use_exam_submission_date == "1") ? $this->exam_submission_date : false;

        //Check Exceptions
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->getID(), $user->getID());
        if (isset($exception) && is_object($exception)) {
            if ($exception->getUseSubmissionDate() == "1") {
                $exc_date_sub = $exception->getSubmissionDate();
                $sub_date = $exc_date_sub;
            }

            if ($exception->getExcluded() == 1) {
                return false;
            }
        } else {
            $use_sub_date = $this->use_exam_submission_date;
        }

        if ($sub_date && $use_sub_date) {
            if ($sub_date >= time() || $sub_date == 0) {
                $submission_valid = true;
            }
        } else {
            $submission_valid = true;
        }

        if ($submission_valid) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the start_time for an exam has passed
     *
     * Checks whether the defined exam start_time (or a defined user start_time exception)
     * is less than or equal time the current time().
     *
     * @param User $user
     * @uses Models_Exam_Post_Exception::fetchRowByPostIdProxyId() to retrieve exceptions
     * @return bool
     */
    public function isAfterUserStartTime(User $user) {
        $start_date = $this->start_date;

        //Check Exceptions
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->getID(), $user->getID());
        if (isset($exception) && is_object($exception)) {
            if ($exception->getUseStartDate() == "1") {
                $exc_date_start = $exception->getStartDate();
                $start_date = $exc_date_start;
            }
        } else {
            $use_start_date = $this->use_exam_start_date;
        }

        if (isset($start_date) && $use_start_date) {
            if ($start_date <= time() || $start_date == 0) {
                $start_valid = true;
            }
        } else {
            $start_valid = true;
        }

        if ($start_valid){
            return true;
        }

        return false;
    }

    /**
     * Determine whether the end_time for an exam has passed
     *
     * Checks whether the defined exam end_time (or a defined user end_time exception)
     * is greater than or equal time the current time().
     *
     * @param User $user
     * @uses Models_Exam_Post_Exception::fetchRowByPostIdProxyId() to retrieve exceptions
     * @return bool
     */
    public function isBeforeUserEndTime(User $user) {
        $end_date = $this->end_date;
        $use_end_date = $this->use_exam_end_date;

        //Check Exceptions
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->getID(), $user->getID());
        if (isset($exception) && is_object($exception)) {
            if ($exception->getUseEndDate()) {
                $exc_date_end = $exception->getEndDate();
                $end_date = $exc_date_end;
            }
        } else {
            $use_end_date = $this->use_exam_end_date;
        }

        if (isset($end_date) && $use_end_date) {
            if ($end_date >= time() || $end_date == 0) {
                $end_valid = true;
            }
        } else {
            $end_valid = true;
        }

        if ($end_valid) {
            return true;
        }

        return false;
    }

    /**
     * @deprecated Deprecated in favor of using boolean helper methods isBeforeUserEndTime(), isAfterUserStartTime(), etc.
     * @param $ENTRADA_USER
     * @return array
     */
    public function check_exam_times($ENTRADA_USER) {
        $post       = $this;
        $check_str_t = false;
        $check_end_t = false;
        $check_sub_t = false;
        $new_attempt = false;
        $disable_new_attempt = false;
        $continue_attempt = false;

        $start_date = $post->getStartDate();
        $end_date   = $post->getEndDate();
        $sub_date   = $post->getSubmissionDate();

        //over rides the dates based on the exception dates if they're clicked to use
        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());
        if (isset($exception) && is_object($exception)) {
            $exc_use_date_start = $exception->getUseStartDate();
            $exc_use_date_end   = $exception->getUseEndDate();
            $exc_use_date_sub   = $exception->getUseSubmissionDate();

            if ($exc_use_date_start) {
                $exc_date_start     = $exception->getStartDate();
                $start_date = $exc_date_start;
            }

            if ($exc_use_date_end) {
                $exc_date_end       = $exception->getEndDate();
                $end_date = $exc_date_end;
            }

            if ($exc_use_date_sub) {
                $exc_date_sub       = $exception->getSubmissionDate();
                $sub_date = $exc_date_sub;
            }
        }

        if (isset($start_date)) {
            if ($start_date <= time() || $start_date == 0) {
                $check_str_t = true;
            }
        } else {
            $check_str_t = true;
        }

        if (isset($end_date)) {
            if ($end_date >= time() || $end_date == 0) {
                $check_end_t = true;
            }
        } else {
            $check_end_t = true;
        }

        if (isset($sub_date)) {
            if ($sub_date >= time() || $sub_date == 0) {
                $check_sub_t = true;
            }
        } else {
            $check_sub_t = true;
        }

        if ($check_str_t === true && $check_end_t === true) {
            $new_attempt = true;
        }

        if ($start_date <= time() && $end_date >= time()) {
            $access_time = true;
        }

        if ($check_sub_t) {
            $continue_attempt = true;
        } else {
            $disable_new_attempt = true;
        }

        $return_array = array(
            "check_str_t" => $check_str_t,
            "check_end_t" => $check_end_t,
            "check_sub_t" => $check_sub_t,
            "start_date"  => $start_date,
            "end_date"    => $end_date,
            "sub_date"    => $sub_date,
            "new_attempt" => $new_attempt,
            "continue_attempt" => $continue_attempt,
            "disable_new_attempt" => $disable_new_attempt,
        );

        return $return_array;
    }
    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllEventExamsByProxyID($proxy_id, $include_all = false, $include_secure = false) {
        global $db;

        $output = false;

        $query = "SELECT a.*, b.`event_id`, b.`course_id`, b.`event_title`, b.`event_start`, b.`event_duration`, c.`course_name`, c.`course_code`, d.`audience_type`, d.`audience_value`
                    FROM `" . self::TABLE_NAME . "` AS a
                    JOIN `events` AS b
                    ON a.`target_type` = 'event'
                    AND	b.`event_id` = a.`target_id`
                    JOIN `courses` AS c
                    ON c.`course_id` = b.`course_id`
                    JOIN `event_audience` AS d
                    ON b.`event_id` = d.`event_id`
                    WHERE c.`course_active` = '1'";
        $query .= ($include_secure == false ? " AND a.`secure` = '0'" : "");
        $query .= " AND a.`hide_exam` = '0'
                    AND a.`deleted_date` IS NULL";

        $query .= ($include_all == false) ? " AND a.`end_date` >= " . time() : "";
        $query .= " ORDER BY b.`event_start` DESC";

        $results = $db->GetAll($query);
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $audience_member = false;
                //Do a really complex query for something that should be easy to pull up in an ORM
                switch ($result["audience_type"]) {
                    case "course_id" : // Course Audience
                        $cperiod_id = 0;

                        $event = Models_Event::fetchRowByID($result["event_id"]);
                        if ($event) {
                            $cperiod = $event->getCurriculumPeriod();
                            if ($cperiod) {
                                $cperiod_id = $cperiod->getID();
                            }
                        }

                        $query = "SELECT u.*
                                    FROM `group_members` a
									RIGHT JOIN `course_audience` b
									ON b.`audience_type` = 'group_id'
									AND b.`audience_value` = a.`group_id`
                                    AND b.`cperiod_id` = " . $db->qstr($cperiod_id) . "
									RIGHT JOIN `" . AUTH_DATABASE . "`.`user_data` u
									ON a.`proxy_id` = u.`id`
									OR (b.`audience_type` = 'proxy_id'
									AND b.`audience_value` = u.`id`)
									WHERE b.`course_id` = " . $db->qstr($result["audience_value"]) . "
									AND u.`id` = " . $db->qstr($proxy_id) . "
									GROUP BY u.`id`";
                        $course_audience = $db->getAll($query);
                        if ($course_audience) {
                            $audience_member = true;
                        }
                        break;
                    case "group_id" : // Course Groups
                        $query = "	SELECT u.* FROM
									`course_group_audience` a
									JOIN `" . AUTH_DATABASE . "`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`cgroup_id` = " . $db->qstr($result["audience_value"]) . "
                                    WHERE u.`id` = " . $db->qstr($proxy_id);
                        $group_audience = $db->getAll($query);
                        if ($group_audience) {
                            $audience_member = true;
                        }
                        break;
                    case "cohort" :    // Cohorts
                        $query = "	SELECT u.* FROM
									`group_members` a
									JOIN `" . AUTH_DATABASE . "`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`group_id` = " . $db->qstr($result["audience_value"]) . "
									WHERE u.`id` = " . $db->qstr($proxy_id);
                        $group_audience = $db->getAll($query);
                        if ($group_audience) {
                            $audience_member = true;
                        }
                        break;
                    case "proxy_id" : // Learners
                        $query = "	SELECT u.* FROM
									`" . AUTH_DATABASE . "`.`user_data` u
									WHERE u.`id` = " . $db->qstr($result["audience_value"]) . "
									AND u.`id` = " . $db->qstr($proxy_id);
                        $user_audience = $db->getAll($query);
                        if ($user_audience) {
                            $audience_member = true;
                        }
                        break;
                }
                if ($audience_member === true) {
                    $output[] = new self($result);
                }
                $audience_member = false;
            }
        }
        return $output;
    }

    public function canEditExam() {
        global $ENTRADA_ACL;
        $exam   = $this->getExam();
        $update = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update");
        return $update;
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllSecureEventExamsByProxyID($proxy_id, $include_all = false) {
        global $db;

        $output = false;

        $query = "SELECT a.*, b.`event_id`, b.`course_id`, b.`event_title`, b.`event_start`, b.`event_duration`, c.`course_name`, c.`course_code`, d.`audience_type`, d.`audience_value`
                    FROM `".self::TABLE_NAME."` AS a
                    JOIN `events` AS b
                    ON a.`target_type` = 'event'
                    AND	b.`event_id` = a.`target_id`
                    JOIN `courses` AS c
                    ON c.`course_id` = b.`course_id`
                    JOIN `event_audience` as d
                    ON b.event_id = d.event_id
                    WHERE c.`course_active` = '1'
                    AND a.`secure` = '1'
                    AND a.`hide_exam` = '0'
                    AND a.`deleted_date` IS NULL";

        $query .= ($include_all !== true) ? " AND (a.`end_date` >= " . time() . " OR a.`end_date` IS NULL)": "";
        $query .= " ORDER BY b.`event_start` DESC";

        $results = $db->GetAll($query);

        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $audience_member = false;
                //Do a really complex query for something that should be easy to pull up in an ORM
                switch ($result["audience_type"]) {
                    case "course_id" : // Course Audience
                        $query = "	SELECT u.*, d.`active` AS `has_attendance` FROM
									`group_members` a
									RIGHT JOIN `course_audience` b
									ON b.`audience_type` = 'group_id'
									AND b.`audience_value` = a.`group_id`
									RIGHT JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									OR (b.`audience_type` = 'proxy_id'
									AND b.`audience_value` = u.`id`)
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($result['event_id'])."
									WHERE b.`course_id` = ".$db->qstr($result["audience_value"])."
									AND u.`id` = ".$db->qstr($proxy_id)."
									GROUP BY u.`id`";
                        $course_audience = $db->getAll($query);
                        if ($course_audience) {
                            $audience_member = true;
                        }
                        break;
                    case "group_id" : // Course Groups
                        $query = "	SELECT u.*, d.`active` AS `has_attendance` FROM
									`course_group_audience` a
									JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`cgroup_id` = ".$db->qstr($result["audience_value"])."
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($result['event_id'])."
                                    WHERE u.`id` = ".$db->qstr($proxy_id);
                        $group_audience = $db->getAll($query);
                        if ($group_audience) {
                            $audience_member = true;
                        }
                        break;
                    case "cohort" :	// Cohorts
                        $query = "	SELECT u.*, d.`active` AS `has_attendance` FROM
									`group_members` a
									JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`group_id` = ".$db->qstr($result["audience_value"])."
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($result['event_id'])."
									WHERE u.`id` = ".$db->qstr($proxy_id);
                        $group_audience = $db->getAll($query);
                        if ($group_audience) {
                            $audience_member = true;
                        }
                        break;
                    case "proxy_id" : // Learners
                        $query = "	SELECT u.*, d.`active` AS `has_attendance` FROM
									`".AUTH_DATABASE."`.`user_data` u
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($result['event_id'])."
									WHERE u.`id` = ".$db->qstr($result["audience_value"])."
									AND u.`id` = ".$db->qstr($proxy_id);
                        $user_audience = $db->getAll($query);
                        if ($user_audience) {
                            $audience_member = true;
                        }
                        break;
                }

                $post_id = $result["post_id"];
                if ($audience_member === true) {
                    if (is_array($output) && !array_key_exists($post_id, $output)) {
                        $output[$post_id] = new self($result);
                    }
                }
                $audience_member = false;
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllSecureCommunityExamsByProxyID($proxy_id) {
        global $db;

        $output = false;

        $query = "SELECT a.*, b.`community_id`, b.`community_url`, b.`community_title`, CONCAT('[', b.`community_title`, '] ', bp.`menu_title`) AS `page_title`, bp.`page_url`, c.`proxy_id`
                    FROM `".self::TABLE_NAME."` AS a
                    JOIN `communities` AS b
                    ON a.`target_type` = 'community'
                    JOIN `community_pages` AS bp
                    ON a.`target_type` = 'community'
                    AND	bp.`cpage_id` = a.`target_id`
                    AND bp.`community_id` = b.`community_id`
                    JOIN `community_members` as c
                    ON bp.`community_id` = c.`community_id`
                    WHERE b.`community_active` = '1'
                    AND bp.`page_active` = '1'
                    AND c.`proxy_id` = ".$db->qstr($proxy_id)."
                    AND a.`secure` = '1'
                    ORDER BY b.`community_title` ASC";
        $results = $db->GetAll($query);
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Exam_Post[] */
    public static function fetchAllSecureExamsByProxyID($proxy_id){

        return array_merge(self::fetchAllSecureEventExamsByProxyID($proxy_id), self::fetchAllSecureCommunityExamsByProxyID($proxy_id));
    }


    /**
     * Returns array of Category Result Details for all learners in the audience for this post
     * @todo Review this method as it may be out of date
     *
     * @return array
     */
    public function scoreCategoryResultDetails(){
        $objective_collection = array();
        $post = $this;
        $progress_records = Models_Exam_Progress::fetchAllStudentsByPostIDProgressValue($post->getPostID(), "submitted");

        if (!empty($progress_records)) {
            foreach ($progress_records as $record) {
                $progress_id = $record->getID();
                $proxy_id = $record->getProxyID();
                $categories = array();
                $objectives_correct = array();
                $objectives_incorrect = array();
                $exam_elements = Models_Exam_Exam_Element::fetchAllScoredQuestionsByExamID($post->getExamID());

                if (!empty($exam_elements)) {
                    foreach ($exam_elements as $element) {
                        $correct = 0;
                        $user_point = 0;
                        $question = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                        $element_point = (int)$element->getAdjustedPoints();
                        $progress_response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($progress_id, $element->getExamElementID());
                        if ($progress_response) {
                            $user_point = (int)$progress_response->getScore();
                        }

                        if ($element_point === $user_point) {
                            $correct = 1;
                        }

                        $curriculum_tags = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question->getQuestionID());
                        if (!empty($curriculum_tags)) {
                            foreach ($curriculum_tags as $tag) {
                                $objective_id = $tag->getObjectiveID();
                                $global_objective = Models_Objective::fetchRow($objective_id);
                                if ($global_objective) {

                                    $set_parent = $global_objective->getRoot(); //Look into creating a stored procedure for this
                                    $set = $set_parent->getID();
                                    $categories[$set][$objective_id][] = array(
                                        "exam_element_id" => $element->getExamElementID(),
                                        "version_id" => $element->getElementID(),
                                        "correct" => $correct,
                                        "set" => $set_parent,
                                        "objective" => $global_objective
                                    );

                                    if ($correct) {
                                        $objectives_correct[$set][$objective_id]++;
                                    } else {
                                        $objectives_incorrect[$set][$objective_id]++;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($categories)) {
                    foreach ($categories as $set_id => $sets) {
                        if (!empty($sets)) {
                            foreach ($sets as $objective_id => $objectives) {
                                $correct = (int)$objectives_correct[$set_id][$objective_id];
                                $incorrect = (int)$objectives_incorrect[$set_id][$objective_id];
                                $total = $correct + $incorrect;
                                $score = ($correct / $total) * 100; //number_format is probably unnecessary. leave it to the view to worry about presentation
                                $value = ($correct ? $correct : 0);

                                $category_details = Models_Exam_Category_Result_Detail::fetchRowByProxyIdObjectiveIdProgressId($proxy_id, $objective_id, $progress_id);
                                if (!$category_details) {

                                    $category_details = new Models_Exam_Category_Result_Detail(array(
                                        "proxy_id" => $proxy_id,
                                        "exam_progress_id" => $progress_id,
                                        "post_id" => $record->getPostID(),
                                        "exam_id" => $record->getExamID(),
                                        "objective_id" => $objective_id,
                                        "set_id" => $set_id,
                                        "score" => $score,
                                        "value" => $value,
                                        "possible_value" => $total,
                                        "updated_date" => time(),
                                    ));
                                } else {

                                    $category_details->setScore($score);
                                    $category_details->setValue($value);
                                    $category_details->setUpdatedDate(time());
                                }

                                $objective_collection[$objective_id][$proxy_id] = $category_details;
                            }
                        }
                    }
                }
            }
        }

        return $objective_collection;
    }


    /**
     * Generates the Category Result Detail for a specified exam progress
     *
     * @todo Review and make sure its up to date
     * @param Models_Exam_Progress $progress
     * @return array
     */
    public function scoreProgressCategoryResultDetails(Models_Exam_Progress $progress){
        $objective_collection = array();
        $category_result_details_collection = array();
        $post = $this;
        $progress_record = Models_Exam_Progress::fetchRowByID($progress->getID());

        if ($progress_record) {
            $progress_id = $progress_record->getID();
            $proxy_id = $progress_record->getProxyID();
            $exam_elements = Models_Exam_Exam_Element::fetchAllScoredQuestionsByExamID($post->getExamID());

            if (!empty($exam_elements)) {
                foreach ($exam_elements as $element) {
                    $correct = 0;
                    $user_point = 0;
                    $question = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                    $element_point = (int)$element->getAdjustedPoints();
                    $progress_response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($progress_id, $element->getExamElementID());
                    if ($progress_response) {
                        $user_point = (int)$progress_response->getScore();
                    }

                    if ($element_point === $user_point) {
                        $correct = 1;
                    }
                    $curriculum_tags = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question->getQuestionID());
                    if (!empty($curriculum_tags)) {;
                        foreach ($curriculum_tags as $tag) {
                            $objective_id = (int) $tag->getObjectiveID();
                            $global_objective = Models_Objective::fetchRow($objective_id);
                            if ($global_objective) {

                                $set_root = $global_objective->getRoot();
                                $set_root_id = $set_root->getID();

                                if ($correct) {
                                    $objective_collection[$set_root_id][$objective_id]["correct"][] = $element;
                                } else {
                                    $objective_collection[$set_root_id][$objective_id]["incorrect"][] = $element;
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($objective_collection)) {
                foreach ($objective_collection as $set_id => $objectives) {
                    if (!empty($objectives)) {
                        foreach ($objectives as $objective_id => $objective) {
                            $correct = count($objective["correct"]);
                            $incorrect = count($objective["incorrect"]);
                            $total = $correct + $incorrect;
                            $score = ($correct / $total) * 100; //number_format is probably unnecessary. leave it to the view to worry about presentation
                            $value = ($correct ? $correct : 0);

                            $category_details = new Models_Exam_Category_Result_Detail(array(
                                "proxy_id" => $proxy_id,
                                "exam_progress_id" => $progress_id,
                                "post_id" => $post->getID(),
                                "exam_id" => $post->getExamID(),
                                "objective_id" => $objective_id,
                                "set_id" => $set_id,
                                "score" => $score,
                                "value" => $value,
                                "possible_value" => $total,
                                "updated_date" => time(),
                            ));

                            $category_result_details_collection[$objective_id] = $category_details;
                        }
                    }
                }
            }
        }

        return $category_result_details_collection;
    }

    /**
     * Generates the Category Result Detail for a specified exam progress and objective
     *
     * @param Models_Exam_Progress $progress
     * @param Models_Objective $objective
     * @return bool|Models_Exam_Category_Result_Detail
     */
    public function scoreProgressCategoryResultDetail(Models_Exam_Progress $progress, Models_Objective $objective) {
        $objective_collection = array();
        $category_result_detail = false;
        $post = $this;
        $progress_record = Models_Exam_Progress::fetchRowByID($progress->getID());

        if ($progress_record) {
            $progress_id = $progress_record->getID();
            $proxy_id = $progress_record->getProxyID();
            $exam_elements = Models_Exam_Exam_Element::fetchAllScoredQuestionsByExamID($post->getExamID());

            if (!empty($exam_elements)) {
                foreach ($exam_elements as $element) {
                    $correct = 0;
                    $user_point = 0;
                    $question = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                    $element_point = (int)$element->getAdjustedPoints();
                    $progress_response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($progress_id, $element->getExamElementID());
                    if ($progress_response) {
                        $user_point = (int)$progress_response->getScore();
                    }

                    if ($element_point === $user_point) {
                        $correct = 1;
                    }
                    $exam_objective = Models_Exam_Question_Objectives::fetchRowByQuestionIdObjectiveId($question->getQuestionID(), $objective->getID());

                    if ($exam_objective) {
                        if ($objective) {
                            if ($correct) {
                                $objective_collection["correct"][] = $element;
                            } else {
                                $objective_collection["incorrect"][] = $element;
                            }
                        }
                    }

                }
                if (!empty($objective_collection)) {
                    $correct = count($objective_collection["correct"]);
                    $incorrect = count($objective_collection["incorrect"]);
                    $total = $correct + $incorrect;
                    $score = ($correct / $total) * 100; //number_format is probably unnecessary. leave it to the view to worry about presentation
                    $value = ($correct ? $correct : 0);

                    $category_result_detail = new Models_Exam_Category_Result_Detail(array(
                        "proxy_id" => $proxy_id,
                        "exam_progress_id" => $progress_id,
                        "post_id" => $post->getID(),
                        "exam_id" => $post->getExamID(),
                        "objective_id" => $objective->getID(),
                        "set_id" => $objective->getRoot()->getID(),
                        "score" => $score,
                        "value" => $value,
                        "possible_value" => $total,
                        "updated_date" => time(),
                    ));
                }
            }
        }

        return $category_result_detail;
    }

    /**
     * @param array $categoryResultDetails Must be in the format $objective_collection[$objective_id][$proxy_id] = $category_details
     * @return array
     */
    public function scoreCategoryResultsFromDetails(array $categoryResultDetails){
        $post = $this;
        $objective_collection = $categoryResultDetails;
        $category_results = array();
        if (!empty($objective_collection)) {
            foreach ($objective_collection as $objective_id => $proxy_array) {
                if (!empty($proxy_array)) {
                    $score_array = array();
                    $score_added = 0;
                    $total = count($proxy_array);

                    foreach ($proxy_array as $proxy_id => $details) {
                        $score = (int)$details->getScore();
                        $score_added = $score_added + ($score ? $score : 0);
                        $score_array[] = ($score ? $score : 0);

                        if (!empty($score_array)) {
                            $min = min($score_array);
                            $max = max($score_array);
                            $average = ($total ? $score_added / $total : 0);
                            $category_result = array(
                                $post->getPostID(),
                                $post->getExamID(),
                                $details->getObjectiveID(),
                                $details->getSetID(),
                                $average,
                                $min,
                                $max,
                                $total,
                                time()
                            );

                            $category_results[] = $category_result;
                        }
                    }
                }
            }
        }

        return $category_results;
    }

    /**
     * Generates a category report for a specified objective
     *
     * @param Models_Objective $objective
     * @return bool|Models_Exam_Category_Result
     */
    public function scoreCategory(Models_Objective $objective) {
        $category_result = false;

        $exam_id = $this->getExamID();
        $objective_id = $objective->getID();
        $elements = Models_Exam_Exam_Element::fetchScorableCategoryQuestionElementsByExamIDObjectiveID($exam_id, $objective_id); //All the questions with that objective
        $response_collection = array();
        $scores = array();
        $correct = 0;
        $progress = Models_Exam_Progress::fetchAllByPostID($this->getID());
        $total_exam_takers = count($progress);

        if (!empty($elements)) {
            foreach ($elements as $element) { //all the questions for the objective
                $element_point = (int)$element->getAdjustedPoints();
                $progress_responses = Models_Exam_Progress_Responses::fetchAllStudentResponsesByElementIDPostID($element->getID(), $this->getID());
                if (!empty($progress_responses)) {
                    foreach ($progress_responses as $progress_response) {
                        $user_point = 0;
                        $proxy_id = $progress_response->getProxyID();
                        if ($progress_response) {
                            $user_point = (int)$progress_response->getScore();
                        }

                        if ($element_point === $user_point) {
                            $response_collection[$proxy_id]["correct"][] = $element->getID();
                            $correct++;
                        } else {
                            $response_collection[$proxy_id]["incorrect"][] = $element->getID();
                        }
                    }
                }
            }
            if (!empty($response_collection)) {
                foreach ($response_collection as $proxy_id => $score) {
                    $correct_response = count($score["correct"]);
                    $incorrect_response = count($score["incorrect"]);
                    $total = $correct_response + $incorrect_response;
                    $score = ($correct_response / $total) * 100;
                    $scores[] = $score;
                }
            }

            if ($scores && is_array($scores) && !empty($scores)) {
                $total              = count($scores);
                $min                = min($scores);
                $max                = max($scores);
                $average            = array_sum($scores) / $total;
                $total_questions    = count($elements);
                $total_responses    = $total_questions * $total_exam_takers;
                $possible_value     = count($elements) * $total_questions;
                $total_correct      = (int) $correct;
                $percent_correct    = ($total_questions > 0) ? number_format(($total_correct/$total_responses) * 100, 2) : 0;
                $total_incorrect    = $total_responses - $correct;
                $percent_incorrect  = ($total_questions > 0) ? number_format(($total_incorrect/$total_responses) * 100, 2) : 0;
                $level              = "";

                $category_result = new Models_Exam_Category_Result(array(
                    "post_id" => $this->getID(),
                    "exam_id" => $this->getExamID(),
                    "objective_id" => $objective->getID(),
                    "set_id" => $objective->getRoot()->getID(),
                    "average" => $average,
                    "min" => $min,
                    "max" => $max,
                    "total_exam_takers" => $total_exam_takers,
                    "percent_correct"  => $percent_correct,
                    "total_correct" => $total_correct,
                    "percent_incorrect"  => $percent_incorrect,
                    "total_incorrect" => $total_incorrect,
                    "total_questions" => $total_questions,
                    "total_responses" => $total_responses,
                    "possible_value" => $possible_value, //examtakers*#quetions
                    "updated_date" => time(),
                ));
            }


        }
        return $category_result;
    }

    public function scoreCategoriesNew(){
        $post = $this;
        $category_results = array();

        $progress_records = Models_Exam_Progress::fetchAllStudentsByPostIDProgressValue($post->getPostID(), "submitted");
        if (!empty($progress_records)){
            $category_result_details = array();
            foreach($progress_records as $progress_record){
                array_merge($category_result_details, $this->scoreProgressCategoryResultDetails($progress_record));
            }
        }

        if (!empty($category_result_details)) {
            $score_array = array();
            $score_added = 0;
            $total = count($category_result_details);
            foreach ($category_result_details as $category_result_detail) {

                $score = (int)$category_result_detail->getScore();
                $score_added = $score_added + ($score ? $score : 0);
                $score_array[] = ($score ? $score : 0);
            }

            if (!empty($score_array)) {
                $min = min($score_array);
                $max = max($score_array);
                $average = ($total ? $score_added / $total : 0);

                $category_result = new Models_Exam_Category_Result(array(
                    "post_id" => $post->getID(),
                    "exam_id" => $post->getExamID(),
                    "objective_id" => $category_result_detail->getObjectiveID(),
                    "set_id" => $category_result_detail->getSetID(),
                    "average" => $average,
                    "min" => $min,
                    "max" => $max,
                    "possible_value" => $total,
                    "updated_date" => time(),
                ));

                $category_results[] = $category_result;

            }
        }

        return $category_results;
    }

    public function scoreCategories() {
        global $db;
        $post = $this;

        $progress_records = Models_Exam_Progress::fetchAllStudentsByPostIDProgressValue($post->getPostID(), "submitted");

        /**
         * Note: $progress_records && is_array($progress_records) is unnecessary in this case because Models_Base::fetchAll() always returns an array
         */
        if (!empty($progress_records)) {
            $objective_collection = array();
            $insert_collection = array();
            $update_collection = array();

            foreach ($progress_records as $record) {
                $progress_id = $record->getID();
                $proxy_id = $record->getProxyID();
                $categories = array();
                $objectives_correct = array();
                $objectives_incorrect = array();
                $exam_elements = Models_Exam_Exam_Element::fetchAllScoredQuestionsByExamID($post->getExamID());

                if (!empty($exam_elements)) {
                    foreach ($exam_elements as $element) {
                        $correct = 0;
                        $user_point = 0;
                        $question = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                        $element_point = (int) $element->getAdjustedPoints();
                        $progress_response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($progress_id, $element->getExamElementID());
                        if ($progress_response) {
                            $user_point = (int) $progress_response->getScore();
                        }

                        if ($element_point === $user_point) {
                            $correct = 1;
                        }

                        $curriculum_tags = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question->getQuestionID());
                        if (!empty($curriculum_tags)) {
                            foreach ($curriculum_tags as $tag) {
                                $objective_id = $tag->getObjectiveID();
                                $global_objective = Models_Objective::fetchRow($objective_id);
                                if ($global_objective) {

                                    $set_parent = $global_objective->getRoot(); //Look into creating a stored procedure for this
                                    $set = $set_parent->getID();
                                    $categories[$set][$objective_id][] = array(
                                        "exam_element_id" => $element->getExamElementID(),
                                        "version_id" => $element->getElementID(),
                                        "correct" => $correct,
                                        "set" => $set_parent,
                                        "objective" => $global_objective
                                    );

                                    if ($correct) {
                                        $objectives_correct[$set][$objective_id]++;
                                    } else {
                                        $objectives_incorrect[$set][$objective_id]++;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($categories)){
                    foreach ($categories as $set_id => $sets) {
                        if (!empty($sets)) {
                            foreach ($sets as $objective_id => $objectives) {
                                $correct = (int) $objectives_correct[$set_id][$objective_id];
                                $incorrect = (int) $objectives_incorrect[$set_id][$objective_id];
                                $total = $correct + $incorrect;

                                $score = ($correct / $total) * 100; //number_format is probably unnecessary. leave it to the view to worry about presentation
                                $value = ($correct ? $correct : 0);

                                $result_details = array(
                                    $proxy_id,
                                    $progress_id,
                                    $record->getPostID(),
                                    $record->getExamID(),
                                    $objective_id,
                                    $set_id,
                                    $score,
                                    $value,
                                    $total,
                                    time(),
                                );

                                $category_details = Models_Exam_Category_Result_Detail::fetchRowByProxyIdObjectiveIdProgressId($proxy_id, $objective_id, $progress_id);
                                if (!$category_details) {

                                    $result_details_list = implode(",", $result_details);
                                    $insert_collection[] = "(" . $result_details_list . ")";

                                    $category_details = new Models_Exam_Category_Result_Detail(array(
                                        "proxy_id" => $proxy_id,
                                        "exam_progress_id" => $progress_id,
                                        "post_id" => $record->getPostID(),
                                        "exam_id" => $record->getExamID(),
                                        "objective_id" => $objective_id,
                                        "set_id" => $set_id,
                                        "score" => $score,
                                        "value" => $value,
                                        "possible_value" => $total,
                                        "updated_date" => time(),
                                    ));
                                } else {
                                    array_unshift($result_details, $category_details->getID());
                                    $result_details_list = implode(",", $result_details);
                                    $update_collection[] = "(" . $result_details_list . ")";

                                    $category_details->setScore($score);
                                    $category_details->setValue($value);
                                    $category_details->setUpdatedDate(time());
                                }

                                $objective_collection[$objective_id][$proxy_id] = $category_details;
                            }
                        }
                    }
                }
            }

            if (!empty($update_collection)){
                $update_sql  = "INSERT INTO `exam_category_result_detail` (`detail_id`, `proxy_id`, `exam_progress_id`, `post_id`, `exam_id`, `objective_id`, `set_id`, `score`, `value`, `possible_value`, `updated_date`) VALUES " . implode(", ", $update_collection) . " ";
                $update_sql .= "ON DUPLICATE KEY UPDATE `score` = VALUES(`score`), `value` = VALUES(`value`), `updated_date` = VALUES(`updated_date`)";

                if (!$db->Execute($update_sql)) {
                    add_error("Error could not update the exam category result details");
                }
            }
            if (!empty($insert_collection)){
                $insert_sql = "INSERT INTO `exam_category_result_detail` (`proxy_id`, `exam_progress_id`, `post_id`, `exam_id`, `objective_id`, `set_id`, `score`, `value`, `possible_value`, `updated_date`) VALUES " . implode(", ", $insert_collection);
                if (!$db->Execute($insert_sql)) {
                    add_error("Error could not insert the exam category result details");
                }
            }

            if (!has_error()) {
                //$values = array();
                if (!empty($objective_collection)) {
                    $insert_results_collection = array();
                    $update_results_collection = array();

                    foreach ($objective_collection as $objective_id => $proxy_array) {
                        if (!empty($proxy_array)) {
                            $score_array    = array();
                            $score_added    = 0;
                            $total          = count($proxy_array);

                            foreach ($proxy_array as $proxy_id => $details) {
                                $score = (int) $details->getScore();
                                $score_added = $score_added + ($score ? $score : 0);
                                $score_array[] = ($score ? $score : 0);

                                /**
                                 * Commenting out the block of code below, because $values does not appear to be used
                                 */
//                                $value = (int) $details->getValue();
//                                if (array_key_exists($objective_id, $values)) {
//                                    $values[$objective_id] = $value;
//                                }
                            }

                            if (!empty($score_array)) {
                                $min        = min($score_array);
                                $max        = max($score_array);
                                $average    = ($total ? $score_added / $total : 0);
                                $category_result = array(
                                    $post->getPostID(),
                                    $post->getExamID(),
                                    $details->getObjectiveID(),
                                    $details->getSetID(),
                                    $average,
                                    $min,
                                    $max,
                                    $total,
                                    time()
                                );

                                $results = Models_Exam_Category_Result::fetchRowByObjectiveIdPostId($objective_id, $post->getPostID());
                                if (!$results) {
                                    $result_list = implode(",", $category_result);
                                    $insert_results_collection[] = "(" . $result_list . ")";

                                } else {
                                    array_unshift($category_result, $results->getID());
                                    $result_details_list = implode(",", $category_result);
                                    $update_results_collection[] = "(" . $result_details_list . ")";
                                }
                            }
                        }
                    }

                    if (!empty($update_results_collection)){
                        $update_results_sql  = "INSERT INTO `exam_category_result` (`result_id`, `post_id`, `exam_id`, `objective_id`, `set_id`, `average`, `min`, `max`, `possible_value`, `updated_date`) VALUES " . implode(", ", $update_results_collection) . " ";
                        $update_results_sql .= "ON DUPLICATE KEY UPDATE `average` = VALUES(`average`), `min` = VALUES(`min`), `max` = VALUES(`max`), `possible_value` = VALUES(`possible_value`), `updated_date` = VALUES(`updated_date`)";

                        if (!$db->Execute($update_results_sql)) {
                            add_error("Error could not update the exam category result");
                        }
                    }
                    if (!empty($insert_results_collection)){
                        $insert_results_sql = "INSERT INTO `exam_category_result` (`post_id`, `exam_id`, `objective_id`, `set_id`, `average`, `min`, `max`, `possible_value`, `updated_date`) VALUES " . implode(", ", $insert_results_collection);
                        if (!$db->Execute($insert_results_sql)) {
                            add_error("Error could not insert the exam category result");
                        }
                    }
                }
            }
        }

        if (!has_error()) {
            return true;
        } else {
            return false;
        }
    }
}