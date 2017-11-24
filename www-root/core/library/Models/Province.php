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
 * A model for handling Provinces/States.
 *
 * @author Organisation: Queen's University
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Province extends Models_Base {
    protected $province_id, $country_id, $province, $abbreviation;

    protected static $table_name = "global_lu_provinces";
    protected static $primary_key = "province_id";
    protected static $default_sort_column = "province";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->province_id;
    }

    public function getProvinceID() {
        return $this->province_id;
    }

    public function getCountryID() {
        return $this->country_id;
    }

    public function getProvince() {
        return $this->province;
    }

    public function getAbbreviation() {
        return $this->abbreviation;
    }

    /* @return bool|Models_Province */
    public static function fetchRowByID($province_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "province_id", "value" => $province_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Province */
    public static function fetchRowByProvinceName($province) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "province", "value" => $province, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Province[] */
    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "province_id", "value" => 0, "method" => ">=")));
    }

    /* @return ArrayObject|Models_Province[] */
    public static function fetchAllByCountryID($country_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "country_id", "value" => $country_id, "method" => "=")));
    }

    /* @return bool|Models_Province */
    public static function fetchRowByIDCountryID($id, $country_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "province_id", "value" => $id, "method" => "="),
            array("key" => "country_id", "value" => $country_id, "method" => "=")
        ));
    }
}