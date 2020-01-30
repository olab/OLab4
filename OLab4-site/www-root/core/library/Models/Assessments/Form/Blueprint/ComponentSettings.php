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
 * A model for handling form blueprint components settings
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Blueprint_ComponentSettings extends Models_Base {
    protected $aftc_setting_id, $form_type_id, $component_order, $settings, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_form_type_component_settings";
    protected static $primary_key = "aftc_setting_id";
    protected static $default_sort_column = "form_type_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aftc_setting_id;
    }

    public function getAftcSettingID() {
        return $this->aftc_setting_id;
    }

    public function getFormTypeID() {
        return $this->form_type_id;
    }

    public function getComponentOrder() {
        return $this->component_order;
    }

    public function getSettings() {
        return $this->settings;
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

    public static function fetchRowByID($aftc_setting_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aftc_setting_id", "value" => $aftc_setting_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "aftc_setting_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchRowByFormTypeComponentOrder($form_type_id, $component_order) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_type_id", "value" => $form_type_id, "method" => "="),
            array("key" => "component_order", "value" => $component_order, "method" => "="),
        ));
    }
}