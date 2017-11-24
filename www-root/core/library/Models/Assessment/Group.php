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
 * A model for handling assessment groups
 *
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 bitHeads, Inc. All Rights Reserved.
 */

class Models_Assessment_Group extends Models_Base {
    protected $agroup_id, $cgroup_id, $assessment_id;

    protected static $table_name = "assessment_groups";
    protected static $primary_key = "agroup_id";
    protected static $default_sort_column = "cgroup_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->agroup_id;
    }

    public function getAgroupID() {
        return $this->agroup_id;
    }

    public function getCgroupID() {
        return $this->cgroup_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public static function fetchRowByID($agroup_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "agroup_id", "value" => $agroup_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "agroup_id", "value" => 0, "method" => ">=")));
    }

    public function fetchAllByAssessmentID() {
        return $this->fetchAll(array(
            array("key" => "assessment_id", "value" => $this->assessment_id, "method" => "=")
        ));
    }

    public function fetchAllWithGroupNameByAssessmentID() {
        global $db;

        $query = "SELECT * FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    JOIN course_groups b
                    ON a.cgroup_id = b.cgroup_id
                    WHERE a.assessment_id = ?";

        $results = $db->getAll($query, array($this->assessment_id));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function deleteAllByAssessmentID() {
        global $db;

        $query = "DELETE FROM `".DATABASE_NAME."`.`".static::$table_name."`
                    WHERE assessment_id = ?";

        $result = $db->Execute($query, array($this->assessment_id));

        return $result;
    }
}