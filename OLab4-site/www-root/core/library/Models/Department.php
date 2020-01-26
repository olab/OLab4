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
 * A model for handling departments
 *
 * @author Organisation: Queen's University
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Department extends Models_Base {
    protected $department_id, $organisation_id, $entity_id, $parent_id, $department_title, $department_address1, $department_address2, $department_city, $department_province, $province_id, $department_country, $country_id, $department_postcode, $department_telephone, $department_fax, $department_email, $department_url, $department_desc, $department_active, $updated_date, $updated_by;

    protected static $database_name = AUTH_DATABASE;
    protected static $table_name = "departments";
    protected static $primary_key = "department_id";
    protected static $default_sort_column = "department_title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->department_id;
    }

    public function getDepartmentID() {
        return $this->department_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getEntityID() {
        return $this->entity_id;
    }

    public function getParentID() {
        return $this->parent_id;
    }

    public function getDepartmentTitle() {
        return $this->department_title;
    }

    public function getDepartmentAddress1() {
        return $this->department_address1;
    }

    public function getDepartmentAddress2() {
        return $this->department_address2;
    }

    public function getDepartmentCity() {
        return $this->department_city;
    }

    public function getDepartmentProvince() {
        return $this->department_province;
    }

    public function getProvinceID() {
        return $this->province_id;
    }

    public function getDepartmentCountry() {
        return $this->department_country;
    }

    public function getCountryID() {
        return $this->country_id;
    }

    public function getDepartmentPostcode() {
        return $this->department_postcode;
    }

    public function getDepartmentTelephone() {
        return $this->department_telephone;
    }

    public function getDepartmentFax() {
        return $this->department_fax;
    }

    public function getDepartmentEmail() {
        return $this->department_email;
    }

    public function getDepartmentUrl() {
        return $this->department_url;
    }

    public function getDepartmentDesc() {
        return $this->department_desc;
    }

    public function getDepartmentActive() {
        return $this->department_active;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($department_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "department_id", "value" => $department_id, "method" => "=")
        ));
    }

    public static function fetchRowByName($department_title, $organisation_id, $active = 1) {
        global $db;

        $organisation_id = (int) $organisation_id;
        $active = (($active == 1) ? 1 : 0);

        $query = "SELECT *
                    FROM `" . AUTH_DATABASE . "`.`departments`
                    WHERE `department_title` = ?
                    AND `organisation_id` = ?
                    AND `department_active` = ?";

        return $db->GetRow($query, array($department_title, $organisation_id, $active));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "department_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllRecordsBySearchValue($search_value) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "department_title", "value" => "%".$search_value."%", "method" => "LIKE")));
    }

    public static function fetchAllByOrganisationID($organisation_id, $search_value = null) {
        global $db;

        $query = "	SELECT a.`department_id`, a.`department_title`, b.`entity_title`
                    FROM `".AUTH_DATABASE."`.`departments` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`entity_type` AS b
                    ON a.`entity_id` = b.`entity_id`
                    WHERE a.`organisation_id` = ?
                    AND a.`department_active` = '1'
                    AND a.`department_title` LIKE (". $db->qstr("%". $search_value ."%") .")
                    ORDER BY a.`department_title`";

        $departments = $db->GetAll($query, $organisation_id);

        return $departments ?: false;
    }

    public static function fetchOrganisationTitleByDepartmentID($department_id) {
        global $db;

        $query = "	SELECT b.`organisation_title`
                    FROM `".AUTH_DATABASE."`.`departments` AS a
                    JOIN `".AUTH_DATABASE."`.`organisations` AS b
                    ON a.`organisation_id` = b.`organisation_id`
                    WHERE a.`department_id` = ?";

        $organisation_title = $db->GetOne($query, $department_id);

        return $organisation_title ?: "";
    }
}
