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
                $organisation_id,
                $building_code,
                $building_name,
                $building_address1,
                $building_address2,
                $building_city,
                $building_province,
                $building_country,
                $building_postcode;

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

    public function getOrganisationID() {
        return $this->organisation_id;
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
}