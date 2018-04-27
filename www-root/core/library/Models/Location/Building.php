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
 * A model for handling Buildings.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Location_Building extends Models_Base {
    protected   $building_id,
                $site_id,
                $building_code,
                $building_name,
                $building_address1,
                $building_address2,
                $building_city,
                $building_province,
                $building_country,
                $building_province_id,
                $building_country_id,
                $building_postcode,
                $site;

    protected static $table_name            = "global_lu_buildings";
    protected static $primary_key           = "building_id";
    protected static $default_sort_column   = "building_name";


    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->building_id;
    }

    public function getBuildingID() {
        return $this->building_id;
    }

    public function getSiteId() {
        return $this->site_id;
    }

    public function getBuildingCode() {
        return $this->building_code;
    }

    public function getBuildingName() {
        return $this->building_name;
    }

    public function getBuildingAddress1() {
        return $this->building_address1;
    }

    public function getBuildingAddress2() {
        return $this->building_address2;
    }

    public function getBuildingCity() {
        return $this->building_city;
    }

    public function getBuildingProvince() {
        return $this->building_province;
    }

    public function getBuildingCountry() {
        return $this->building_country;
    }

    public function getBuildingProvinceID() {
        return $this->building_province_id;
    }

    public function getBuildingCountryID() {
        return $this->building_country_id;
    }

    public function getBuildingPostcode() {
        return $this->building_postcode;
    }

    /* @return bool|Models_Location_Building */
    public static function fetchRowByID($building_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "building_id", "value" => $building_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Location_Building */
    public static function fetchRowByName($building_name, $organisation_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "building_name", "value" => $building_name, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Location_Building */
    public static function fetchRowByCode($building_code, $organisation_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "building_code", "value" => $building_code, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Location_Building[] */
    public static function fetchAllByOrganisationID($organisation_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Location_Building[] */
    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "building_id", "value" => 0, "method" => ">=")));
    }

    /* @return bool|Models_Location_Site */
    public function getSite() {
        if (NULL === $this->site) {
            $this->site = Models_Location_Site::fetchRowByID($this->getSiteId());
        }

        return $this->site;
    }

    /* @return ArrayObject|Models_Location_Building[] */
    public static function fetchBuildingsWithRoomsBySiteId($site_id) {
        global $db;

        $items = false;

        $query = "  SELECT b.*
                    FROM `global_lu_buildings` b
                    JOIN `global_lu_rooms` r
                    ON b.`building_id` = r.`building_id`
                    WHERE b.`site_id` = ?
                    GROUP BY b.`building_id`
                    ORDER BY b.`building_name` ASC";
        $results = $db->GetAll($query, [$site_id]);

        if ($results) {
            foreach ($results as $result) {
                $item = new self($result);
                $items[] = $item;
            }
        }

        return $items;
    }

    /* @return ArrayObject|Models_Location_Building[] */
    public static function fetchAllBySiteID($site_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "site_id", "value" => $site_id, "method" => "=")
        ));
    }
}
