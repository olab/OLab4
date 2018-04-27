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
 * Model for managing objectives associated with an item response
 *
 * @author Organisation: Queens University
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2018 Queens University. All Rights Reserved.
 */

class Models_Assessments_Item_Response_Objective extends Models_Base {

    protected $irobjective_id;
    protected $iresponse_id;
    protected $objective_id;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessments_lu_item_response_objectives";
    protected static $primary_key = "irobjective_id";
    protected static $default_sort_column = "iresponse_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->irobjective_id;
    }

    public function getIrobjectiveID() {
        return $this->irobjective_id;
    }

    public function setIrobjectiveID($irobjective_id) {
        $this->irobjective_id = $irobjective_id;

        return $this;
    }

    public function getIresponseID() {
        return $this->iresponse_id;
    }

    public function setIresponseID($iresponse_id) {
        $this->iresponse_id = $iresponse_id;

        return $this;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function setObjectiveID($objective_id) {
        $this->objective_id = $objective_id;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public static function fetchRowByID($irobjective_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "irobjective_id", "method" => "=", "value" => $irobjective_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "irobjective_id", "method" => ">=", "value" => 0)));
    }

    /**
     * Fetch a row by objective_id and iresponse_id
     * @param $objective_id
     * @param $iresponse_id
     * @param null $deleted_date
     * @return bool|Models_Base
     */
    public function fetchRowByIresponseIDObjectiveID($iresponse_id, $objective_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "iresponse_id", "method" => "=", "value" => $iresponse_id),
            array("key" => "objective_id", "method" => "=", "value" => $objective_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    public static function deleteByID($id) {
        if ($this_record = self::fetchRowByID($id)) {
            $this_record->setDeletedDate(time());
            return $this_record->update();
        }
        return false;
    }

    public static function deleteByIresponseID($iresponse_id, $updated_by) {
        global $db;
        $query = "UPDATE `cbl_assessments_lu_item_response_objectives` SET `deleted_date` = ?, `updated_date` = ?, `updated_by` = ? WHERE `iresponse_id` = ? ";
        return $db->Execute($query, array(time(), time(), $updated_by, $iresponse_id));
    }

    public static function deleteAllByFormID($form_id, $updated_by) {
        global $db;
        $query = "
            UPDATE `cbl_assessments_lu_item_response_objectives` AS iro
            
            JOIN `cbl_assessments_lu_item_responses` AS ir 
              ON ir.`iresponse_id` = iro.`iresponse_id`
            JOIN `cbl_assessments_lu_items` AS i 
              ON ir.`item_id` = i.`item_id`
            JOIN `cbl_assessment_form_elements` AS fe 
              ON fe.`element_type` = 'item' AND fe.`element_id` = i.`item_id`
            
            SET iro.`updated_by` = ?, iro.`deleted_date` = ?, iro.`updated_date` = ?
            
            WHERE fe.`form_id` = ? 
              AND iro.`deleted_date` IS NULL";
        return $db->Execute($query, array($updated_by, time(), time(), $form_id));
    }

}