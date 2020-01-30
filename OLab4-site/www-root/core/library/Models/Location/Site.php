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
 * Model for handling location Sites
 *
 * @author Organisation: Queen's University
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 */


class Models_Location_Site extends Models_Base {
    protected $site_id;
    protected $site_code;
    protected $site_name;
    protected $site_address1;
    protected $site_address2;
    protected $site_city;
    protected $site_province_id;
    protected $site_country_id;
    protected $site_postcode;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;
    protected $deleted_by;
    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "global_lu_sites";
    protected static $primary_key = "site_id";
    protected static $default_sort_column = "site_name";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->site_id;
    }

    public function getSiteID() {
        return $this->site_id;
    }

    public function setSiteID($site_id) {
        $this->site_id = $site_id;
        return $this;
    }

    public function getSiteCode() {
        return $this->site_code;
    }

    public function setSiteCode($site_code) {
        $this->site_code = $site_code;
        return $this;
    }

    public function getSiteName() {
        return $this->site_name;
    }

    public function setSiteName($site_name) {
        $this->site_name = $site_name;
        return $this;
    }

    public function getSiteAddress1() {
        return $this->site_address1;
    }

    public function setSiteAddress1($site_address1) {
        $this->site_address1 = $site_address1;
        return $this;
    }

    public function getSiteAddress2() {
        return $this->site_address2;
    }

    public function setSiteAddress2($site_address2) {
        $this->site_address2 = $site_address2;
        return $this;
    }

    public function getSiteCity() {
        return $this->site_city;
    }

    public function setSiteCity($site_city) {
        $this->site_city = $site_city;
        return $this;
    }

    public function getSiteProvinceID() {
        return $this->site_province_id;
    }

    public function setSiteProvinceID($site_province_id) {
        $this->site_province_id = $site_province_id;
        return $this;
    }

    public function getSiteCountryID() {
        return $this->site_country_id;
    }

    public function setSiteCountryID($site_country_id) {
        $this->site_country_id = $site_country_id;
        return $this;
    }

    public function getSitePostcode() {
        return $this->site_postcode;
    }

    public function setSitePostcode($site_postcode) {
        $this->site_postcode = $site_postcode;
        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
        return $this;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;
        return $this;
    }

    public static function fetchRowByID($site_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "site_id", "method" => "=", "value" => $site_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "site_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }
        return $this->update();
    }

    public static function fetchAllSitesHavingBuildingsAndRooms($organisation_id) {
        global $db;

        $query = "SELECT *
                  FROM `global_lu_sites` s 
                  JOIN `global_lu_sites_organisation` o 
                  ON o.`organisation_id` = ?
                  AND o.`site_id` = s.`site_id`
                  JOIN `global_lu_buildings` b 
                  ON s.`site_id` = b.`site_id`
                  JOIN `global_lu_rooms` r 
                  ON b.`building_id` = r.`building_id`
                  WHERE s.`deleted_date` IS NULL
                  GROUP BY s.`site_id`";

        $results = $db->getAll($query, [$organisation_id]);

        $sites = [];

        if ($results) {
            foreach ($results as $result) {
                $site = new self($result);
                $sites[] = $site;
            }
        }
        return $sites;
    }
}