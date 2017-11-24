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
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Adjustment extends Models_Base {
    protected   $ep_adjustment_id,
                $exam_element_id,
                $exam_id,
                $type,
                $value,
                $created_date,
                $created_by,
                $deleted_date;

    protected static $table_name = "exam_adjustments";
    protected static $primary_key = "ep_adjustment_id";
    protected static $default_sort_column = "ep_adjustment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->ep_adjustment_id;
    }
    
    public function getExamElementID() {
        return $this->exam_element_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }
    
    public function getType() {
        return $this->type;
    }
    
    public function getValue() {
        return $this->value;
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

    public function setDeletedDate($date) {
        $this->deleted_date = $date;
    }
    
    public static function setAllDeletedByElementIDExamIDType($exam_element_id, $exam_id, $type) {
        global $db;
        $ret = true;
        $db->StartTrans();
        $adjustments = self::fetchAllByElementIDExamIDType($exam_element_id, $exam_id, $type);
        if (is_array($adjustments)) {
            foreach ($adjustments as $adjustment) {
                $adjustment->setDeletedDate(time());
                if (!$adjustment->update()) {
                    $db->FailTrans();
                    $ret = false;
                    break;
                }
            }
        } else {
            $db->FailTrans();
            $ret = false;
        }
        $db->CompleteTrans();
        return $ret;
    }

    /* @return bool|Models_Exam_Adjustment */
    public static function fetchRowByID($ep_adjustment_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "ep_adjustment_id", "value" => $ep_adjustment_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    /* @return bool|Models_Exam_Adjustment */
    public static function fetchRowByElementIDExamIDType($exam_element_id, $exam_id, $type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "type", "value" => $type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    /* @return bool|Models_Exam_Adjustment */
    public static function fetchRowByElementIDExamIDTypeValue($exam_element_id, $exam_id, $type, $value, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "type", "value" => $type, "method" => "="),
            array("key" => "value", "value" => $value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Exam_Adjustment */
    public static function fetchRowByElementIDExamIDValue($exam_element_id, $exam_id, $value, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "value", "value" => $value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Adjustment[] */
    public static function fetchAllByElementIDExamID($exam_element_id, $exam_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    /* @return bool|Models_Exam_Adjustment[] */
    public static function fetchAllByElementIDExamIDType($exam_element_id, $exam_id, $type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "="),
            array("key" => "exam_id", "value" => $exam_id, "method" => "="),
            array("key" => "type", "value" => $type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Adjustment[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}
