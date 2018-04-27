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
 * A model for handling procedure attributes to EPAs
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_CBME_ProcedureEPAAttribute extends Models_Base {

    protected $epa_attribute_id;
    protected $course_id;
    protected $epa_objective_id;
    protected $attribute_objective_id;
    protected $created_by;
    protected $created_date;
    protected $updated_by;
    protected $updated_date;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbme_procedure_epa_attributes";
    protected static $primary_key = "epa_attribute_id";
    protected static $default_sort_column = "epa_attribute_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->epa_attribute_id;
    }

    public function getEpaAttributeID() {
        return $this->epa_attribute_id;
    }

    public function setEpaAttributeID($epa_attribute_id) {
        $this->epa_attribute_id = $epa_attribute_id;

        return $this;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function setCourseID($course_id) {
        $this->course_id = $course_id;

        return $this;
    }

    public function getEpaObjectiveID() {
        return $this->epa_objective_id;
    }

    public function setEpaObjectiveID($epa_objective_id) {
        $this->epa_objective_id = $epa_objective_id;

        return $this;
    }

    public function getAttributeObjectiveID() {
        return $this->attribute_objective_id;
    }

    public function setAttributeObjectiveID($attribute_objective_id) {
        $this->attribute_objective_id = $attribute_objective_id;

        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public static function fetchRowByID($epa_response_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "epa_response_id", "method" => "=", "value" => $epa_response_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "epa_response_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    /**
     * Return an array structure of procedure attributes objectives
     * given the parent_objective_id is a procedure objective ID
     *
     * @param $organisation_id
     * @param $parent_objective_id
     * @return array
     */
    public static function buildProcedureAttributeTree($organisation_id, $parent_objective_id) {
        $result = array();
        $children_records = Models_Objective::fetchAllByParentID($organisation_id, $parent_objective_id);
        if (empty($children_records)) {
            return array();
        }
        foreach ($children_records as $i => $child) {
            $result[$child->getID()] = $child->toArray();
            $result[$child->getID()]["children"] = self::buildProcedureAttributeTree($organisation_id, $child->getID());
        }
        return $result;
    }

    /**
     * Return a flat array of procedure attributes objective ID
     * given the parent_objective_id is a procedure objective ID
     *
     * @param $organisation_id
     * @param $parent_objective_id
     * @return array
     */
    public static function fetchProcedureAttributeObjectiveIDs($organisation_id, $parent_objective_id) {
        $result = array();
        $children_records = Models_Objective::fetchAllByParentID($organisation_id, $parent_objective_id);
        if (empty($children_records)) {
            return array();
        }
        foreach ($children_records as $i => $child) {
            $result[] = $child->getID();
            $result = array_merge($result, self::fetchProcedureAttributeObjectiveIDs($organisation_id, $child->getID()));
        }
        return $result;
    }

    /**
     * Soft delete epa to attributes link for previously uploaded data
     *
     * @param int $proxy_id
     * @param int $course_id
     * @param array $epas
     * @param array $attributes
     * @return bool
     */
    public static function disableByCourseIDEPAObjectivesIDAttributes($proxy_id, $course_id, $epas = array(), $attributes = array()) {
        global $db;

        if (!is_array($epas) || !count($epas) || !is_array($attributes) || !count($attributes)) {
            return false;
        }
        if (empty($epas) || empty($attributes)) {
            return false;
        }
        // Ensure the array has only integers
        $clean_ids = array_filter(
            array_map(function ($v) {
                return clean_input($v, array("trim", "int"));
            }, $epas)
        );
        if (empty($clean_ids)) {
            return false;
        }
        // Implode, and quit if end up with nothing
        $epas = implode(",", $clean_ids);

        $clean_ids = array_filter(
            array_map(function ($v) {
                return clean_input($v, array("trim", "int"));
            }, $attributes)
        );
        if (empty($clean_ids)) {
            return false;
        }
        // Implode, and quit if end up with nothing
        $attributes = implode(",", $clean_ids);

        /**
         * Fetch objectives that are only attached to the given EPA set and delete them
         *
         * TODO: This query needs optimization. Its goal is to deactivate any procedure attribute records stored in global_lu_objectives
         *  that are no longer associated with any of the specified EPAs. However, leaving active objectives that are not linked to
         *  anything should not be a problem for now.
         **/
        /*
        $query = "SELECT `attribute_objective_id` 
                  FROM `cbme_procedure_epa_attributes` 
                  WHERE `attribute_objective_id` NOT IN (
                      SELECT `attribute_objective_id` 
                      FROM `cbme_procedure_epa_attributes` 
                      WHERE `attribute_objective_id` IN (
                          SELECT `attribute_objective_id`
                          FROM `cbme_procedure_epa_attributes`
                          WHERE `course_id` = ?
                          AND `epa_objective_id` IN (" . $epas . ")
                      )
                      AND `course_id` = ?
                      AND `epa_objective_id` NOT IN (" . $epas . ")
                  )
                  AND `course_id` = ?
                  AND `epa_objective_id` IN (" . $epas . ")";

        if ($obj_to_delete = $db->GetCol($query, array($course_id, $course_id, $course_id))) {
            $objectives = array();

            foreach ($obj_to_delete as $objective) {
                if ($objective_obj = Models_Objective::fetchRow($objective)) {
                    $objectives[] = $objective_obj;
                }
            }

            if (count($objectives)) {
                Models_Objective::deleteAllWithAllChildren($objectives, $organisation_id);
            }
        }
        */

        $query = "UPDATE `cbme_procedure_epa_attributes` 
                  SET `deleted_date` = ?,
                      `updated_date` = ?,
                      `updated_by` = ?
                  WHERE `course_id` = ?
                  AND `epa_objective_id` IN ($epas)
                  AND `attribute_objective_id` IN ($attributes)";

        return $db->Execute($query, array(time(), time(), $proxy_id, $course_id));
    }

    /**
     * Associate a given attribute to a list of EPAs
     *
     * @param int $proxy_id
     * @param int $course_id
     * @param int $attribute_objective_id
     * @param array $epas
     * @return bool
     */
    public static function insertBulkByCourseIDObjectiveIDEPAObjectives($proxy_id, $course_id, $attribute_objective_id, $epas) {
        global $db;

        $inserts = array();
        $params = array();
        foreach ($epas as $epa) {
            $inserts[] = "(?, ?, ?, ?, ?)";
            $params = array_merge($params, array($course_id, $epa, $attribute_objective_id, $proxy_id, time()));
        }

        if (!count($inserts)) {
            return false;
        }

        $query = "INSERT INTO `cbme_procedure_epa_attributes` (`course_id`, `epa_objective_id`, `attribute_objective_id`, `created_by`, `created_date`) VALUES ".
            implode(",", $inserts);

        return $db->Execute($query, $params);
    }

    /**
     * Return A list of EPAs for which attributes have been uploaded for the specified procedure/course.
     *
     * @param $course_id
     * @param $procedure_id
     * @return mixed
     */
    public static function getUploadedAttributesEPAsList($course_id, $procedure_id) {
        global $db;

        $query = "  SELECT * FROM (SELECT d.`objective_code`, d.`objective_order`, b.`created_date`, a.`objective_id`, b.`epa_objective_id`, d.`objective_name`
                        FROM `global_lu_objectives` AS a
                        JOIN `cbme_procedure_epa_attributes` AS b ON a.`objective_id` = b.`attribute_objective_id`
                        JOIN `global_lu_objectives` AS d ON b.`epa_objective_id` = d.`objective_id`
                        WHERE a.`objective_parent` = ?
                        AND a.`objective_active` = 1
                        AND b.`course_id` = ?
                        ORDER BY `created_date` DESC
                    ) AS t1 GROUP BY `objective_code` ORDER BY `objective_order`";

        return $db->GetAll($query, array($procedure_id, $course_id));
    }

    /**
     * Fetches procedure attributes by objective id and procedure id.
     * @param $objective_id
     * @param $procedure_id
     * @return mixed
     */
    public function getAttributesByObjectiveIDProcedureID($objective_id, $procedure_id) {
        global $db;

        $query = "  SELECT pa.`attribute_objective_id`, go.`objective_name` 
                    FROM `cbme_procedure_epa_attributes` as pa
                    JOIN `global_lu_objectives` as go
                    ON pa.`attribute_objective_id` = go.`objective_id`
                    WHERE pa.`epa_objective_id` = ?
                    AND go.`objective_parent` = ?
                    AND pa.`deleted_date` IS NULL";

        return $db->GetAll($query, array($objective_id, $procedure_id));
    }
}