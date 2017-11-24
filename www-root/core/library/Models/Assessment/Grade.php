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
 * A model for handling assessment grades
 *
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 bitHeads, Inc. All Rights Reserved.
 */

class Models_Assessment_Grade extends Models_Base {
    protected $grade_id, $assessment_id, $proxy_id, $value, $threshold_notified;

    protected static $table_name = "assessment_grades";
    protected static $primary_key = "grade_id";
    protected static $default_sort_column = "assessment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->grade_id;
    }

    public function getGradeID() {
        return $this->grade_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function getThresholdNotified() {
        return $this->threshold_notified;
    }

    public static function fetchRowByID($grade_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "grade_id", "value" => $grade_id, "method" => "=")
        ));
    }

    public function fetchRowByAssessmentIDProxyID() {
        return $this->fetchRow(array(
            array("key" => "assessment_id", "value" => $this->assessment_id, "method" => "="),
            array("key" => "proxy_id", "value" => $this->proxy_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "grade_id", "value" => 0, "method" => ">=")));
    }
}