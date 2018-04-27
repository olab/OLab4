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

class Models_Assessments_Form_Blueprint_RatingScales extends Models_Base {
    protected $afblueprint_rating_scale_id, $organisation_id, $rating_scale_id, $afblueprint_element_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_form_blueprint_rating_scales";
    protected static $primary_key = "afblueprint_rating_scale_id";
    protected static $default_sort_column = "afblueprint_rating_scale_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afblueprint_rating_scale_id;
    }

    public function getAfblueprintRatingScaleID() {
        return $this->afblueprint_rating_scale_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getRatingScaleID() {
        return $this->rating_scale_id;
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

    public static function fetchRowByID($afblueprint_rating_scale_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afblueprint_rating_scale_id", "value" => $afblueprint_rating_scale_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "afblueprint_rating_scale_id", "value" => 0, "method" => ">=")));
    }

    public static function deleteAllByOrganisationIDAfblueprintElementID($organisation_id, $blueprint_element_id, $proxy_id) {
        global $db;
        $query = "UPDATE `cbl_assessments_form_blueprint_rating_scales` SET `deleted_date` = ?, `updated_date` = ?, `updated_by` = ? WHERE `organisation_id` = ? AND `afblueprint_element_id` = ? AND `deleted_date` IS NULL";
        return $db->Execute($query, array(time(), time(), $proxy_id, $organisation_id, $blueprint_element_id));
    }

    public static function fetchAllByFormBlueprintID($form_blueprint_id) {
        global $db;
        $query = "SELECT bps.*
                  FROM `cbl_assessments_form_blueprint_elements` AS bpe
                  JOIN `cbl_assessments_form_blueprint_rating_scales` AS bps ON bpe.`afblueprint_element_id` = bps.`afblueprint_element_id`
                  WHERE bpe.`form_blueprint_id` = ?
                  AND bpe.`deleted_date` IS NULL
                  AND bps.`deleted_date` IS NULL";

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


}