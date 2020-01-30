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

class Models_Assessments_Form_Blueprint_Objective extends Models_Base {
    protected $afblueprint_objective_id, $organisation_id, $objective_id, $associated_objective_id, $afblueprint_element_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;
    protected $objective_name = null, $objective_code = null, $objective_set_id = null, $objective_set_shortname = null; // These are optionally joined fields, and not part of the schema. They are ignored when inserting.

    protected static $table_name = "cbl_assessments_form_blueprint_objectives";
    protected static $primary_key = "afblueprint_objective_id";
    protected static $default_sort_column = "organisation_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afblueprint_objective_id;
    }

    public function getAfblueprintObjectiveID() {
        return $this->afblueprint_objective_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getAssociatedObjectiveID() {
        return $this->associated_objective_id;
    }

    public function getAfblueprintElementID() {
        return $this->afblueprint_element_id;
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

    public static function fetchRowByID($afblueprint_objective_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afblueprint_objective_id", "value" => $afblueprint_objective_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "afblueprint_objective_id", "value" => 0, "method" => ">=")));
    }

    /**
     * Soft-delete the related records for the given blueprint element.
     *
     * @param $organisation_id
     * @param $afblueprint_element_id
     * @param $proxy_id
     * @return mixed
     */
    public static function deleteAllByOrganisationIDAfblueprintElementID($organisation_id, $afblueprint_element_id, $proxy_id) {
        global $db;
        $query = "UPDATE `cbl_assessments_form_blueprint_objectives` SET `deleted_date` = ?, `updated_date` = ?, `updated_by` = ? WHERE `organisation_id` = ? AND `afblueprint_element_id` = ? AND `deleted_date` IS NULL";
        return $db->Execute($query, array(time(), time(), $proxy_id, $organisation_id, $afblueprint_element_id));
    }

    public static function fetchAllByFormBlueprintID($form_blueprint_id) {
        global $db;
        $query = "SELECT bpo.*, o.`objective_set_id`, os.`shortname` AS `objective_set_shortname`, o.`objective_code`, o.`objective_name`
                  FROM `cbl_assessments_form_blueprint_elements` AS bpe
                  JOIN `cbl_assessments_form_blueprint_objectives` AS bpo ON bpe.`afblueprint_element_id` = bpo.`afblueprint_element_id`
                  LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = bpo.`objective_id`
                  LEFT JOIN `global_lu_objective_sets` AS os ON os.`objective_set_id` = o.`objective_set_id`
                  WHERE bpe.`form_blueprint_id` = ?
                  AND bpe.`deleted_date` IS NULL
                  AND bpo.`deleted_date` IS NULL";

        $results = $db->GetAll($query, array($form_blueprint_id));
        if (empty($results)) {
            return $results;
        }
        $objects = array();
        foreach ($results as $result) {
            $self = new self();
            $objects[] = $self->fromArray($result);
        }
        return $objects;
    }

    /**
     * Fetch milestones selected for an EPA for a blueprint element.
     *
     * @param $element_id
     * @param $epa_id
     * @return bool
     */
    public static function fetchAllByElementIDAssociatedObjectiveID($element_id, $epa_id) {
        global $db;

        // First, get the afblueprint_objective_id for the EPA
        $query = "SELECT `afblueprint_objective_id` 
                  FROM `cbl_assessments_form_blueprint_objectives`
                  WHERE `afblueprint_element_id` = ?
                  AND `objective_id` = ?
                  AND `associated_objective_id` IS NULL
                  AND `deleted_date` IS NULL";

        $epa_afblueprint_objective_id = intval($db->getOne($query, array($element_id, $epa_id)));
        if (! $epa_afblueprint_objective_id) {
            return false;
        }

        // Returns all the milestones for it.
        $query = "SELECT a.* 
                  FROM `global_lu_objectives` a
                  WHERE `objective_id` IN (
                    SELECT `objective_id` 
                    FROM `cbl_assessments_form_blueprint_objectives`
                    WHERE `afblueprint_element_id` = ?
                    AND `associated_objective_id` = ?
                    AND `deleted_date` IS NULL
                  ) ORDER BY objective_order";

        $result = $db->getAll($query, array($element_id, $epa_afblueprint_objective_id));
        return $result;
    }
}