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
 * A model for handling form blueprint item templates
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Blueprint_ItemTemplate extends Models_Base {
    protected $afb_item_template_id, $form_type_id, $active, $parent_item, $ordering, $component_order, $item_definition, $created_date, $created_by, $updated_date, $updated_by;

    protected static $table_name = "cbl_assessments_form_blueprint_item_templates";
    protected static $primary_key = "afb_item_template_id";
    protected static $default_sort_column = "ordering";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afb_item_template_id;
    }

    public function getAfbItemTemplateID() {
        return $this->afb_item_template_id;
    }

    public function getFormTypeID() {
        return $this->form_type_id;
    }

    public function getActive() {
        return $this->active;
    }

    public function getParentItem() {
        return $this->parent_item;
    }

    public function getOrdering() {
        return $this->ordering;
    }

    public function getComponentORder() {
        return $this->component_order;
    }

    public function getItemDefinition() {
        return $this->item_definition;
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

    public static function fetchRowByID($afb_item_template_id, $active) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afb_item_template_id", "value" => $afb_item_template_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ));
    }

    public static function fetchAllRecords($active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    public static function fetchAllByFormTypeParentID($form_type_id, $parent_id = 0, $active = 1) {
        $self = new self();
        $result = $self->fetchAll(array(
            array("key" => "form_type_id", "value" => $form_type_id, "mehod" => "="),
            array("key" => "parent_item", "value" => $parent_id, "mehod" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ), "=", "AND", "ordering");
        return $result;
    }
}