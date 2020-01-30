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
 * A Model for handling Category Results Audience Emails
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Category_Audience extends Models_Base {
    protected $audience_id, $category_id, $proxy_id, $updated_date, $updated_by, $deleted_date;

    protected static $table_name           = "exam_category_audience";
    protected static $primary_key          = "audience_id";
    protected static $default_sort_column  = "category_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->audience_id;
    }

    public function getAudienceID() {
        return $this->audience_id;
    }

    public function getCategoryID() {
        return $this->category_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
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

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    /* @return ArrayObject|Models_Exam_Category_Audience[] */
    public static function fetchAllByCategoryID($category_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "category_id", "value" => $category_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Category_Audience */
    public static function fetchRowByCategoryIdProxyId($category_id, $proxy_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "category_id", "value" => $category_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Category_Audience */
    public static function fetchRowByID($audience_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "audience_id", "value" => $audience_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Category_Audience[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "audience_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}