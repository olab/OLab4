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
 * A Model for handling Category Reports
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Category extends Models_Base {
    protected   $category_id,
                $post_id,
                $exam_id,
                $use_release_start_date,
                $use_release_end_date,
                $release_start_date,
                $release_end_date,
                $updated_date,
                $updated_by,
                $deleted_date,
                $sets;

    protected static $table_name           = "exam_category";
    protected static $primary_key          = "category_id";
    protected static $default_sort_column  = "post_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->category_id;
    }

    public function getCategoryID() {
        return $this->category_id;
    }

    public function getPostID() {
        return $this->post_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getUseReleaseStartDate() {
        return $this->use_release_start_date;
    }

    public function getUseReleaseEndDate() {
        return $this->use_release_end_date;
    }

    public function getReleaseStartDate() {
        return $this->release_start_date;
    }

    public function getReleaseEndDate() {
        return $this->release_end_date;
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

    /* @return bool|Models_Exam_Question_Versions */
    public function getSets() {
        if (NULL === $this->sets) {
            $this->sets = Models_Exam_Category_Set::fetchAllByCategoryID($this->category_id);
        }
        return $this->sets;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setReleaseEndDate($release_end_date) {
        $this->release_end_date = $release_end_date;
    }

    public function setReleaseStartDate($release_start_date) {
        $this->release_start_date = $release_start_date;
    }

    public function setUseReleaseEndDate($use_release_end_date) {
        $this->use_release_end_date = $use_release_end_date;
    }

    public function setUseReleaseStartDate($use_release_start_date) {
        $this->use_release_start_date = $use_release_start_date;
    }

    public function isReportReleased(){
        $released = false;
        $start_ok = false;
        $end_ok = false;
        $current_time = time();

        if ($this->use_release_start_date == 1){
            if ($current_time >= $this->release_start_date){
                $start_ok = true;
            }
        } else {
            $start_ok = false;
        }
        if ($this->use_release_end_date == 1){
            if($current_time <= $this->release_end_date){
                $end_ok = true;
            }
        } else {
            $end_ok = true;
        }

        if ($start_ok && $end_ok){
            $released = true;
        }

        return $released;
    }

    public function isUserInAudience(User $user){
        $audience = false;
        $category_report_audience = Models_Exam_Category_Audience::fetchRowByCategoryIdProxyId($this->getID(), $user->getProxyId());
        if ($category_report_audience){
            $audience = true;
        }

        return $audience;
    }

    /* @return bool|Models_Exam_Category */
    public static function fetchRowByPostID($post_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "post_id", "value" => $post_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Category */
    public static function fetchRowByID($category_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "category_id", "value" => $category_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Category[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "category_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}