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
 * A model for liking an assessment
 *
 * @author Organisation: Queens University
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2018 Queens University. All Rights Reserved.
 */

class Models_Assessments_Likes extends Models_Base {

    protected $like_id;
    protected $aprogress_id;
    protected $dassessment_id;
    protected $proxy_id;
    protected $like_type;
    protected $like_value;
    protected $comment;
    protected $created_by;
    protected $created_date;
    protected $updated_by;
    protected $updated_date;
    protected $deleted_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_likes";
    protected static $primary_key = "like_id";
    protected static $default_sort_column = "aprogress_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->like_id;
    }

    public function getLikeID() {
        return $this->like_id;
    }

    public function setLikeID($like_id) {
        $this->like_id = $like_id;

        return $this;
    }

    public function getAprogressID() {
        return $this->aprogress_id;
    }

    public function setAprogressID($aprogress_id) {
        $this->aprogress_id = $aprogress_id;

        return $this;
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function setDassessmentID($dassessment_id) {
        $this->dassessment_id = $dassessment_id;

        return $this;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function setProxyID($proxy_id) {
        $this->proxy_id = $proxy_id;

        return $this;
    }

    public function getLikeType() {
        return $this->like_type;
    }

    public function setLikeType($like_type) {
        $this->like_type = $like_type;

        return $this;
    }

    public function getLikeValue() {
        return $this->like_value;
    }

    public function setLikeValue($like_value) {
        $this->like_value = $like_value;

        return $this;
    }

    public function getComment() {
        return $this->comment;
    }

    public function setComment($comment) {
        $this->comment = $comment;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public static function fetchRowByID($like_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "like_id", "method" => "=", "value" => $like_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "like_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    /**
     * @param int $like_value
     * @param string $like_type
     * @param int $aprogress_id
     * @param int $created_by
     * @param bool $ignore_deleted
     * @param null $deleted_date
     * @return bool|Models_Base
     */
    public function fetchRowByTypeAndIDAndAProgressID($like_value = 0, $like_type = "", $aprogress_id = 0, $created_by = 0, $ignore_deleted = false, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "like_value", "method" => "=", "value" => $like_value),
            array("key" => "like_type", "method" => "=", "value" => $like_type),
            array("key" => "aprogress_id", "method" => "=", "value" => $aprogress_id),
            array("key" => "created_by", "method" => "=", "value" => $created_by)
        );
        if ($ignore_deleted) {
            $constraints[] = array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"));
        }
        return $self->fetchRow($constraints);
    }

}