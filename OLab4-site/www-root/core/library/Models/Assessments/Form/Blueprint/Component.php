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

class Models_Assessments_Form_Blueprint_Component extends Models_Base {
    protected $blueprint_component_id, $shortname, $description, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_form_blueprint_components";
    protected static $primary_key = "blueprint_component_id";
    protected static $default_sort_column = "blueprint_component_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->blueprint_component_id;
    }

    public function getBlueprintComponentID() {
        return $this->blueprint_component_id;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function getDescription() {
        return $this->description;
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

    public static function fetchRowByID($blueprint_component_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "blueprint_component_id", "value" => $blueprint_component_id, "method" => "=")
        ));
    }

    public static function fetchRowByShortname($blueprint_component_shortname) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "shortname", "value" => $blueprint_component_shortname, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "blueprint_component_id", "value" => 0, "method" => ">=")));
    }

}