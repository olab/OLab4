<?php
/**
 * Models_Secure_RpNow
 *
 * A model for handeling RpNow Configuration.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */
class Models_Secure_RpNow extends Models_Base {

    protected $rpnow_id, $exam_url, $exam_sponsor, $rpnow_reviewed_exam, $rpnow_reviewer_notes,
              $exam_post_id, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "rp_now_config";
    protected static $primary_key = "rpnow_id";
    protected static $default_sort_column = "rpnow_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rpnow_id;
    }

    public function getRpnowId()
    {
        return $this->rpnow_id;
    }

    public function getExamUrl()
    {
        return $this->exam_url;
    }

    public function getExamSponsor()
    {
        return $this->exam_sponsor;
    }

    public function getRpnowReviewedExam()
    {
        return $this->rpnow_reviewed_exam;
    }

    public function getRpnowReviewerNotes()
    {
        return $this->rpnow_reviewer_notes;
    }

    public function getExamPostId()
    {
        return $this->exam_post_id;
    }

    public function setDeletedDate($value) {
        $this->deleted_date = $value;
    }

    /* @return bool|Models_Exam_Post */
    public function getPost() {
        if ($this->post === null) {
            return $this->post = Models_Exam_Post::fetchRowByID($this->exam_post_id);
        } else {
            return $this->post;
        }
    }

    /* @return bool|Models_Secure_RpNow */
    public static function fetchRowByID($rpnow_id = NULL, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rpnow_id", "value" => $rpnow_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Secure_RpNow */
    public static function fetchRowByPostID($post_id = NULL, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}