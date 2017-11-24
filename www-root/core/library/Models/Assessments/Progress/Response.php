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
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Progress_Response extends Models_Base {
    protected $epresponse_id, $one45_answer_id, $aprogress_id, $form_id, $adistribution_id, $assessor_type, $assessor_value, $afelement_id, $iresponse_id, $comments, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_progress_responses";
    protected static $primary_key = "epresponse_id";
    protected static $default_sort_column = "epresponse_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->epresponse_id;
    }

    public function getEpresponseID() {
        return $this->epresponse_id;
    }

    public function getOne45AnswerID() {
        return $this->one45_answer_id;
    }

    public function getAprogressID() {
        return $this->aprogress_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getAfelementID() {
        return $this->afelement_id;
    }

    public function getIresponseID() {
        return $this->iresponse_id;
    }

    public function getComments() {
        return $this->comments;
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

    public function getDeletedDate()
    {
        return $this->deleted_date;
    }

    public static function fetchRowByID($epresponse_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "epresponse_id", "value" => $epresponse_id, "method" => "="),
            array("key" => "deleted_date", "value" => (isset($deleted_date) ? $deleted_date : NULL), "method" => (isset($deleted_date) ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByAprogressIDAfelementID($aprogress_id, $afelement_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aprogress_id", "value" => $aprogress_id, "method" => "="),
            array("key" => "afelement_id", "value" => $afelement_id, "method" => "=")
        ));
    }

    public static function fetchRowByAprogressIDIresponseID($aprogress_id, $iresponse_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aprogress_id", "value" => $aprogress_id, "method" => "="),
            array("key" => "iresponse_id", "value" => $iresponse_id, "method" => "=")
        ));
    }

    public static function fetchRowByAprogressIDAfelementIDIresponseID($aprogress_id, $afelement_id, $iresponse_id, $deleted_date = NULL) {
        $self = new self();

        return $self->fetchRow(array(
            array("key" => "aprogress_id", "value" => $aprogress_id, "method" => "="),
            array("key" => "afelement_id", "value" => $afelement_id, "method" => "="),
            array("key" => "iresponse_id", "value" => $iresponse_id, "method" => "="),
            array("key" => "deleted_date", "value" => (isset($deleted_date) ? $deleted_date : NULL), "method" => (isset($deleted_date) ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByAprogressIDAfelementID($aprogress_id, $afelement_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "aprogress_id", "value" => $aprogress_id, "method" => "="),
            array("key" => "afelement_id", "value" => $afelement_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll( array(
            array("key" => "epresponse_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => (isset($deleted_date) ? $deleted_date : NULL), "method" => (isset($deleted_date) ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByAprogressID($aprogress_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "aprogress_id", "value" => $aprogress_id, "method" => "="),
            array("key" => "deleted_date", "value" => (isset($deleted_date) ? $deleted_date : NULL), "method" => (isset($deleted_date) ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByFormID($form_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
        ));
    }

    public function delete() {
        global $db;

        $query = "DELETE FROM `".Models_Assessments_Progress_Response::$table_name."` WHERE `aprogress_id` = ? AND `afelement_id` = ?";
        if ($db->Execute($query, array($this->aprogress_id, $this->afelement_id))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Fetch all of the in-progress or complete progress record IDs by form ID.
     *
     * @param array $form_ids
     * @return false|array
     */
    public static function fetchInProgressAndCompleteProgressResponseIDsByFormIDs($form_ids) {
        global $db;
        $progress_ids = array();
        $clean_form_ids = array_map(function($v){ return clean_input($v, array("trim", "int")); }, $form_ids);
        if (!is_array($clean_form_ids) || empty($clean_form_ids)) {
            return false;
        }
        $id_string = implode(',', $clean_form_ids);
        $sql = "SELECT    DISTINCT(pr.`aprogress_id`)
                FROM      `cbl_assessment_progress_responses` AS pr
                JOIN      `cbl_assessment_progress` AS p ON p.`aprogress_id` = pr.`aprogress_id`
                WHERE     pr.`form_id` IN({$id_string})
                AND       p.`deleted_date`  IS NULL
                AND       pr.`deleted_date` IS NULL
                AND       (p.`progress_value` = 'inprogress' OR p.`progress_value` = 'complete')";
        $progress = $db->GetAll($sql);
        if (is_array($progress)) {
            foreach ($progress as $progress_record) {
                $progress_ids[] = $progress_record["aprogress_id"];
            }
        }
        return $progress_ids;
    }
}