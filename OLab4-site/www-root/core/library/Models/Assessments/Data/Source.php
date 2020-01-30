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

class Models_Assessments_Data_Source extends Models_Base {
    protected $dsource_id, $dstype_id, $source_value, $source_details;

    protected static $table_name = "cbl_assessments_lu_data_sources";
    protected static $primary_key = "dsource_id";
    protected static $default_sort_column = "dsource_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->dsource_id;
    }

    public function getDsourceID() {
        return $this->dsource_id;
    }

    public function getDstypeID() {
        return $this->dstype_id;
    }

    public function getSourceValue() {
        return $this->source_value;
    }

    public function getSourceDetails() {
        return $this->source_details;
    }

    public static function fetchRowByID($dsource_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "dsource_id", "value" => $dsource_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "dsource_id", "value" => 0, "method" => ">=")));
    }
}