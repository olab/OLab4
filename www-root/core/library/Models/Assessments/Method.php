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
 * A model for handling Assessment Types
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Method extends Models_Base {
    protected $assessment_method_id, $shortname, $order, $phases, $created_date, $created_by, $updated_by, $updated_date, $deleted_date;

    protected static $table_name = "cbl_assessment_lu_methods";
    protected static $primary_key = "assessment_method_id";
    protected static $default_sort_column = "order";

    public function getID() {
        return $this->assessment_method_id;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function getPhases() {
        return $this->phases;
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

    public function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "assessment_method_id", "value" => 0, "method" => ">=")));
    }

    /**
     * Fetch an assessment type by the supplied assessment_type_id
     *
     * @param int $assessment_method_id
     * @param int $deleted_date
     * @return Models_Assessments_Method
     *
     */
    public function fetchRowByID($assessment_method_id = 0, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_method_id", "value" => $assessment_method_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /**
     * Fetch all assessment types by the supplied group and organisation id
     *
     * @param string $group
     * @param int $organisation_id
     * @param bool|int $admin
     * @return array
     *
     */
    public function fetchAllByGroupOrganisationID($group = null, $organisation_id = null, $admin = 0) {
        global $db;
        $query = "  SELECT a.`assessment_method_id`, a.`shortname`, b.*, c.*, d.`title`, d.`description`, d.`instructions`, d.`button_text` 
                    FROM `cbl_assessment_lu_methods` AS a
                    JOIN `cbl_assessment_method_organisations` AS b
                    ON a.`assessment_method_id` = b.`assessment_method_id`
                    JOIN `cbl_assessment_method_groups` AS c
                    ON a.`assessment_method_id` = c.`assessment_method_id`
                    JOIN `cbl_assessment_method_group_meta` AS d 
                    ON a.`assessment_method_id` = d.`assessment_method_id` 
                    WHERE b.`organisation_id` = ?
                    AND c.`group` = ?
                    AND (d.`group` = ? OR d.`group` = '')
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND d.`deleted_date` IS NULL
                    AND c.`admin` = ?
                    ORDER BY a.`order`";

        return $db->GetAll($query, array($organisation_id, $group, $group, $admin));
    }

    public function fetchMethodIDByShortname($shortname) {
        $self = new self();
        $row = $self->fetchRow(array(
            array("key" => "shortname", "value" => $shortname, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
        if (!$row) {
            return false;
        }
        return $row->getID();
    }

    public function fetchMethodsByPhasesGreaterThan($phases) {
        $self = new self();
        $rows = $self->fetchAll(array(
            array("key" => "phases", "value" => $phases, "method" => ">"),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
        return $rows;
    }

    public function fetchMethodIDsByShortnames($shortnames) {
        global $db;

        $query = " SELECT `assessment_method_id` FROM `cbl_assessment_lu_methods` WHERE `shortname` IN (".implode(", ", array_map(array($db, "qstr"), $shortnames)).")";

        return $db->GetAll($query);
    }
}