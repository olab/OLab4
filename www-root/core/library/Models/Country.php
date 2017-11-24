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
 * A model for handling Countries.
 *
 * @author Organisation: Queen's University
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Country extends Models_Base {
    protected $countries_id, $country, $abbreviation, $iso2, $isonum;

    protected static $table_name = "global_lu_countries";
    protected static $primary_key = "countries_id";
    protected static $default_sort_column = "country";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->countries_id;
    }

    public function getCountriesID() {
        return $this->countries_id;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getAbbreviation() {
        return $this->abbreviation;
    }

    public function getIso2() {
        return $this->iso2;
    }

    public function getIsonum() {
        return $this->isonum;
    }

    /* @return bool|Models_Country */
    public static function fetchRowByID($countries_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "countries_id", "value" => $countries_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Country */
    public static function fetchRowByCountry($country) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "country", "value" => $country, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Country[] */
    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "countries_id", "value" => 0, "method" => ">=")));
    }
}