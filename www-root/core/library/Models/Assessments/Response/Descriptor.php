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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Response_Descriptor extends Models_Base {
    protected $ardescriptor_id, $organisation_id, $descriptor, $reportable, $order, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_response_descriptors";
    protected static $primary_key = "ardescriptor_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->ardescriptor_id;
    }

    public function getArdescriptorID() {
        return $this->ardescriptor_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getDescriptor() {
        return $this->descriptor;
    }

    public function getReportable() {
        return $this->reportable;
    }

    public function getOrder() {
        return $this->order;
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

    public static function fetchNextOrder() {
        global $db;
        $query = "SELECT MAX(`order`) + 1 FROM `cbl_assessments_lu_response_descriptors`";
        $result = $db->GetOne($query);
        return $result ? $result : "0";
    }

    public static function fetchRowByID($ardescriptor_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "ardescriptor_id", "value" => $ardescriptor_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDIgnoreDeletedDate($ardescriptor_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "ardescriptor_id", "value" => $ardescriptor_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }
    
    public static function fetchAllByOrganisationID($organisation_id = null, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByOrganisationIDSystemType($organisation_id, $system_type, $deleted_date = NULL, $search_value = NULL) {
        global $db;
        $descriptors = false;
        $query = "  SELECT *
                    FROM `cbl_assessments_lu_response_descriptors`
                    WHERE `organisation_id` = ?
                    AND `one45_anchor_value` ".($system_type == "entrada" ? "IS" : "IS NOT")." NULL
                    AND `deleted_date` ".(isset($deleted_date) ? " = ?" : " IS NULL");
        if ($search_value) {
            $query .= " AND `descriptor` LIKE (". $db->qstr("%". $search_value ."%") .")";
        }
        $query .= " GROUP BY `descriptor`
                    ORDER BY `descriptor` ASC";

        $params = array($organisation_id);
        if (isset($deleted_date)) {
            $params = array($organisation_id, $deleted_date);
        }

        $results = $db->GetAll($query, $params);

        if ($results) {
            foreach ($results as $result) {
                $descriptors[] = new self($result);
            }
        }

        return $descriptors;
    }

    public static function fetchAllByOrganisationIDNaturalSort($organisation_id = null, $deleted_date = NULL) {
        global $db;
        $descriptors = false;

        $query = "  SELECT *
                    FROM `cbl_assessments_lu_response_descriptors`
                    WHERE `organisation_id` = ?
                    AND `deleted_date` ".(isset($deleted_date) ? " = ?" : " IS NULL") ."
                    ORDER BY LENGTH(`descriptor`), `descriptor`";

        $params = array($organisation_id);
        if (isset($deleted_date)) {
            $params = array($organisation_id, $deleted_date);
        }

        $results = $db->GetAll($query, $params);

        if ($results) {
            foreach ($results as $result) {
                $descriptors[] = new self($result);
            }
        }
        return $descriptors;
    }

    public static function fetchAllByOrganisationIDAlphabeticalSort($organisation_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))),
            "=", "AND", "descriptor", "ASC", NULL);
    }

    public static function fetchRowByOrganisationIDDescriptorText($organisation_id, $descriptor, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "descriptor", "value" => $descriptor, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchDistinctByOrganisationID($organisation_id, $deleted_date = NULL) {
        global $db;
        $descriptors = false;

        $query = "  SELECT *
                    FROM `cbl_assessments_lu_response_descriptors`
                    WHERE `organisation_id` = ?
                    AND `deleted_date` ".(isset($deleted_date) ? " = ?" : " IS NULL") ."
                    GROUP BY `descriptor`
                    ORDER BY `descriptor`";

        $params = array($organisation_id);
        if (isset($deleted_date)) {
            $params = array($organisation_id, $deleted_date);
        }

        $results = $db->GetAll($query, $params);

        if ($results) {
            foreach ($results as $result) {
                $descriptors[] = new self($result);
            }
        }
        return $descriptors;
    }
}