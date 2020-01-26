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
 * A model for handling assessments rating scales types
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_RatingScale_Type extends Models_Base {
    protected $rating_scale_type_id, $shortname, $organisation_id, $title, $description, $active, $created_by, $created_date, $updated_by, $updated_date, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_rating_scale_types";
    protected static $primary_key = "rating_scale_type_id";
    protected static $default_sort_column = "organisation_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rating_scale_type_id;
    }

    public function getRatingScaleTypeID() {
        return $this->rating_scale_type_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getActive() {
        return $this->active;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function fetchRowByID($rating_scale_type_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_type_id", "value" => $rating_scale_type_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDIncludeDeleted($rating_scale_type_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_type_id", "value" => $rating_scale_type_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($active=1) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    public static function fetchAllByOrganisationID($organisation_id, $active = 1) {
        $self = new self();
        $results = $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ));
        return $results;
    }

    /**
     * Fetch rating scales joined with type information. Returns a flat array, not an object.
     *
     * @param $scale_type_shortname
     * @param $organisation_id
     * @return array
     */
    public static function fetchRatingScalesByShortnameOrganisationID($scale_type_shortname, $organisation_id) {
        global $db;
        $query = "SELECT rs.*, rst.`title` AS `rating_scale_type_name`, rst.`shortname` AS `rating_scale_shortname`
                  FROM `cbl_assessment_rating_scale` AS rs
                  JOIN `cbl_assessments_lu_rating_scale_types` AS rst ON rst.`rating_scale_type_id` = rs.`rating_scale_type`
                  WHERE rs.`organisation_id` = ?
                  AND rst.`shortname` = ?
                  AND rst.`active` = 1
                  AND rs.`deleted_date` IS NULL";

        $results = $db->GetAll($query, array($organisation_id, $scale_type_shortname));
        if (empty($results)) {
            return array();
        }
        return $results;
    }

    /**
     * Fetch rating scales joined with type information for rating scale types that are visible
     * on the dashboard. Returns Flat array;
     *
     * @param $organisation_id
     * @return array
     */
    public static function fetchDashboardVisibleRatingScale($organisation_id) {
        global $db;
        $query = "SELECT rs.*, rst.`title` AS `rating_scale_type_name`, rst.`description` AS `rating_scale_type_description`, rst.`shortname` AS `rating_scale_shortname`
                  FROM `cbl_assessment_rating_scale` AS rs
                  JOIN `cbl_assessments_lu_rating_scale_types` AS rst ON rst.`rating_scale_type_id` = rs.`rating_scale_type`
                  WHERE rs.`organisation_id` = ?
                  AND rst.`dashboard_visibility` = 1
                  AND rst.`active` = 1
                  AND rs.`deleted_date` IS NULL
                  ORDER BY rst.`ordering`, rs.`rating_scale_id`";

        $results = $db->GetAll($query, array($organisation_id));
        if (empty($results)) {
            return array();
        }
        return $results;
    }

    public static function fetchRatingScaleTypeByShortnameOrganisationID($shortname, $organisation_id, $active = 1) {
        $self = new self();
        $results = $self->fetchAll(array(
            array("key" => "shortname", "value" => $shortname, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
        return $results;
    }

    public static function fetchRatingScaleTypesInUse($organisation_id) {
        global $db;
        $query = "SELECT DISTINCT(`rating_scale_type_id`), rst.`title`, rst.`shortname`
                  FROM `cbl_assessment_rating_scale` AS rs
                  JOIN `cbl_assessments_lu_rating_scale_types` AS rst ON rst.`rating_scale_type_id` = rs.`rating_scale_type`
                  WHERE rst.`active` = 1
                  AND rst.`organisation_id` = ?
                  AND rs.`deleted_date` IS NULL
                  AND rst.`deleted_date` IS NULL";
        return $db->GetAll($query, array($organisation_id));
    }
}