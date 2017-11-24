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

class Models_Assessments_Rubric_Label extends Models_Base {
    protected $rlabel_id, $one45_choice_id, $one45_response_num, $label_type, $rubric_id, $item_id, $iresponse_id, $label, $order;

    protected static $table_name = "cbl_assessments_lu_rubric_labels";
    protected static $primary_key = "rlabel_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rlabel_id;
    }

    public function getRlabelID() {
        return $this->rlabel_id;
    }

    public function getOne45ChoiceID() {
        return $this->one45_choice_id;
    }

    public function getOne45ResponseNum() {
        return $this->one45_response_num;
    }

    public function getLabelType() {
        return $this->label_type;
    }

    public function getRubricID() {
        return $this->rubric_id;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function getIResponseID() {
        return $this->iresponse_id;
    }

    public function getLabel() {
        return $this->label;
    }

    public function getOrder() {
        return $this->order;
    }

    public static function fetchRowByID($rlabel_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rlabel_id", "value" => $rlabel_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "rlabel_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllRecordsByRubricIDLabelType($rubric_id, $label_type = "column") {
        $self = new self();
        return $self->fetchAll(array(
                                array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
                                array("key" => "label_type", "value" => $label_type, "method" => "=")
                               ));
    }

    public static function fetchRowByRubricIDItemID($rubric_id, $item_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "item_id", "value" => $item_id, "method" => "=")
        ));
    }
}