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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 */

class Models_Exam_Exam_Version extends Models_Base {
    protected $exam_version_id, $exam_id, $first_parent_id, $immediate_parent_id;

    protected static $table_name = "exam_versions";
    protected static $primary_key = "exam_version_id";
    protected static $default_sort_column = "exam_version_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->exam_version_id;
    }

    public function getExamVersionID() {
        return $this->exam_version_id;
    }

    public function getExamID() {
        return $this->exam_id;
    }

    public function getFirstParentID() {
        return $this->first_parent_id;
    }

    public function getImmediateParentID() {
        return $this->immediate_parent_id;
    }

    public static function fetchRowByID($exam_version_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_version_id", "value" => $exam_version_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "exam_version_id", "value" => 0, "method" => ">=")));
    }   
}