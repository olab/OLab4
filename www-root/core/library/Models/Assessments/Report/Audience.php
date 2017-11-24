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

class Models_Assessments_Report_Audience extends Models_Base {
    protected $araudience_id, $areport_id, $audience_type, $audience_value;

    protected static $table_name = "cbl_assessment_report_audience";
    protected static $primary_key = "araudience_id";
    protected static $default_sort_column = "araudience_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->araudience_id;
    }

    public function getAraudienceID() {
        return $this->araudience_id;
    }

    public function getAreportID() {
        return $this->areport_id;
    }

    public function getAudienceType() {
        return $this->audience_type;
    }

    public function getAudienceValue() {
        return $this->audience_value;
    }

    public static function fetchRowByID($araudience_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "araudience_id", "value" => $araudience_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "araudience_id", "value" => 0, "method" => ">=")));
    }
}