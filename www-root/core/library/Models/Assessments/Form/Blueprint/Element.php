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
 * 
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Blueprint_Element extends Models_Base {
    protected $afblueprint_element_id, $form_blueprint_id, $element_type, $element_value, $text, $editor_state, $default, $comment_type, $component_order, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_form_blueprint_elements";
    protected static $primary_key = "afblueprint_element_id";
    protected static $default_sort_column = "afblueprint_element_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afblueprint_element_id;
    }

    public function getAfblueprintElementID() {
        return $this->afblueprint_element_id;
    }

    public function getFormBlueprintID() {
        return $this->form_blueprint_id;
    }

    public function getElementType() {
        return $this->element_type;
    }

    public function getElementValue() {
        return $this->element_value;
    }

    public function getText() {
        return $this->text;
    }

    public function getEditorState() {
        return $this->editor_state;
    }

    public function getDefault() {
        return $this->default;
    }

    public function getCommentType() {
        return $this->comment_type;
    }

    public function getComponentOrder() {
        return $this->component_order;
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

    public static function fetchRowByID($afblueprint_element_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afblueprint_element_id", "value" => $afblueprint_element_id, "method" => "=")
        ));
    }

    public static function fetchAllByFormBlueprintID($form_blueprint_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "form_blueprint_id", "value" => $form_blueprint_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "afblueprint_element_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchRowByBlueprintIDElementTypeElementValue($blueprint_id, $element_type, $element_value) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_blueprint_id", "value" => $blueprint_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "element_value", "value" => $element_value, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
    }

    public function deleteByElementID($afblueprint_element_id, $proxy_id) {
        global $db;
        $query = "UPDATE `cbl_assessments_form_blueprint_elements` SET `deleted_date` = ?, `updated_date` = ?, `updated_by` = ? WHERE `afblueprint_element_id` = ? AND `deleted_date` IS NULL";
        return $db->Execute($query, array(time(), time(), $proxy_id, $afblueprint_element_id));
    }
}