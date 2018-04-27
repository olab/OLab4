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
 * Model for handling assessments links
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Link extends Models_Base {

    protected $link_id;
    protected $originating_id;
    protected $linked_id;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_linked_assessments";
    protected static $primary_key = "link_id";
    protected static $default_sort_column = "originating_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->link_id;
    }

    public function getLinkID() {
        return $this->link_id;
    }

    public function setLinkID($link_id) {
        $this->link_id = $link_id;
    }

    public function getOriginatingID() {
        return $this->originating_id;
    }

    public function setOriginatingID($originating_id) {
        $this->originating_id = $originating_id;
    }

    public function getLinkedID() {
        return $this->linked_id;
    }

    public function setLinkedID($linked_id) {
        $this->linked_id = $linked_id;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public static function fetchRowByID($link_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "link_id", "method" => "=", "value" => $link_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "link_id", "method" => ">=", "value" => 0)));
    }

    public static function fetchAllByOriginatingID($dassessment_id) {
        $self = new self();
        return $self->fetchAll(
            array(
                array("key" => "originating_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "deleted_date", "method" => "IS", "value" => null)
            )
        );
    }

    public static function fetchAllByLinkedID($dassessment_id) {
        $self = new self();
        return $self->fetchAll(
            array(
                array("key" => "linked_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "deleted_date", "method" => "IS", "value" => null)
            )
        );
    }
}