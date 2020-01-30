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

class Models_Exam_Post_Exception extends Models_Base {
    protected   $ep_exception_id,
                $post_id,
                $proxy_id,
                $use_exception_max_attempts,
                $max_attempts,
                $exception_start_date,
                $exception_end_date,
                $exception_submission_date,
                $use_exception_start_date,
                $use_exception_end_date,
                $use_exception_submission_date,
                $use_exception_time_factor,
                $exception_time_factor,
                $excluded,
                $created_date,
                $created_by,
                $updated_date,
                $updated_by,
                $deleted_date,
                $post;

    protected static $table_name = "exam_post_exceptions";
    protected static $primary_key = "ep_exception_id";
    protected static $default_sort_column = "ep_exception_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->ep_exception_id;
    }

    public function getPostID() {
        return $this->post_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getUseExceptionMaxAttempts() {
        return $this->use_exception_max_attempts;
    }

    public function getAttempts() {
        return $this->max_attempts;
    }

    public function getStartDate() {
        return $this->exception_start_date;
    }

    public function getEndDate() {
        return $this->exception_end_date;
    }

    public function getSubmissionDate() {
        return $this->exception_submission_date;
    }

    public function getUseStartDate() {
        return (int)$this->use_exception_start_date;
    }

    public function getUseEndDate() {
        return (int)$this->use_exception_end_date;
    }

    public function getUseSubmissionDate() {
        return (int)$this->use_exception_submission_date;
    }

    public function getUseExceptionTimeFactor() {
        return (int)$this->use_exception_time_factor;
    }

    public function getExceptionTimeFactor() {
        return $this->exception_time_factor;
    }

    public function getExcluded() {
        return $this->excluded;
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

    /* @return bool|Models_Exam_Post */
    public function getPost() {
        if (NULL === $this->post){
            $this->post = Models_Exam_Post::fetchRowByID($this->post_id);
        }

        return $this->exam;
    }

    public function setUpdatedDate($date) {
        $this->updated_date = $date;
    }

    public function setUpdatedBy($proxy_id) {
        $this->updated_by = $proxy_id;
    }

    public function setDeletedDate($date) {
        $this->deleted_date = $date;
    }

    public function setAttempts($number) {
        $this->max_attempts = $number;
    }

    public function setStartDate($number) {
        $this->exception_start_date = $number;
    }

    public function setEndDate($number) {
        $this->exception_end_date = $number;
    }

    public function setSubmissionDate($number) {
        $this->exception_submission_date = $number;
    }

    public function setUseStartDate($number) {
        $this->use_exception_start_date = $number;
    }

    public function setUseEndDate($number) {
        $this->use_exception_end_date = $number;
    }

    public function setUseSubmissionDate($number) {
        $this->use_exception_submission_date = $number;
    }

    public function setUseExceptionTimeFactor($number) {
        $this->use_exception_time_factor = $number;
    }

    public function setExceptionTimeFactor($number) {
        $this->exception_time_factor = $number;
    }

    public function setExcluded($getExcluded) {
        $this->excluded = $getExcluded;
    }

    public function setUseExceptionMaxAttempts($use_exception_max_attempts) {
        $this->use_exception_max_attempts = $use_exception_max_attempts;
    }

    /* @return bool|Models_Exam_Post_Exception */
    public static function fetchRowByID($ep_exception_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "ep_exception_id", "value" => $ep_exception_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Post_Exception */
    public static function fetchRowByPostIdProxyId($post_id, $proxy_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Post_Exception */
    public static function fetchRowByPostIdProxyIdExcluded($post_id, $proxy_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "excluded", "value" => 1, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post_Exception[] */
    public static function fetchAllByPostID($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Post_Exception[] */
    public static function fetchAllByPostIDExcluded($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "excluded", "value" => 1, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }


    /* @return ArrayObject|Models_Exam_Post_Exception[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @$old_exceptions ArrayObject|Models_Exam_Post_Exception[] */
    public static function getProxyIds($old_exceptions) {
        if (isset($old_exceptions) && is_array($old_exceptions)) {
            $proxy_ids = array();
            foreach ($old_exceptions as $old_exception) {
                if (isset($old_exception) && is_object($old_exception)) {
                    $proxy_id = (int) $old_exception->getProxyID();
                    if (!in_array($proxy_id, $proxy_ids)) {
                        $proxy_ids[] = $proxy_id;
                    }
                }
            }
            return $proxy_ids;
        } else {
            return false;
        }
    }
}