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
 * This model is for handling the audience related to an User Disclaimer
 *
 * @author Organisation: Queens University
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_Disclaimer_Audience extends Models_Base {

    protected $disclaimer_audience_id;
    protected $disclaimer_id;
    protected $disclaimer_audience_type;
    protected $disclaimer_audience_value;
    protected $updated_date;
    protected $updated_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "disclaimer_audience";
    protected static $primary_key = "disclaimer_audience_id";
    protected static $default_sort_column = "disclaimer_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->disclaimer_audience_id;
    }

    public function getDisclaimerAudienceID() {
        return $this->disclaimer_audience_id;
    }

    public function setDisclaimerAudienceID($disclaimer_audience_id) {
        $this->disclaimer_audience_id = $disclaimer_audience_id;
    }

    public function getDisclaimerID() {
        return $this->disclaimer_id;
    }

    public function setDisclaimerID($disclaimer_id) {
        $this->disclaimer_id = $disclaimer_id;
    }

    public function getDisclaimerAudienceType() {
        return $this->disclaimer_audience_type;
    }

    public function setDisclaimerAudienceType($disclaimer_audience_type) {
        $this->disclaimer_audience_type = $disclaimer_audience_type;
    }

    public function getDisclaimerAudienceValue() {
        return $this->disclaimer_audience_value;
    }

    public function setDisclaimerAudienceValue($disclaimer_audience_value) {
        $this->disclaimer_audience_value = $disclaimer_audience_value;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public static function fetchRowByID($disclaimer_audience_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_audience_id", "method" => "=", "value" => $disclaimer_audience_id)
        ));
    }

    public static function fetchRowByDisclaimerID($disclaimer_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id)
        ));
    }

    public static function fetchRowByAudienceTypeAudienceValue($disclaimer_audience_type = "", $disclaimer_audience_value = "") {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_audience_type", "method" => "=", "value" => $disclaimer_audience_type),
            array("key" => "disclaimer_audience_value", "method" => "=", "value" => $disclaimer_audience_value)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "disclaimer_audience_id", "method" => ">=", "value" => 0)));
    }

    public static function fetchAllByDisclaimerID($disclaimer_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id)));
    }

    public static function fetchAllByAudienceTypeAudienceValue($disclaimer_audience_type = "", $disclaimer_audience_value = Array()) {
        global $db;
        $result = $db->GetAll("   SELECT * 
                         FROM `" . static::$database_name . "`.`" . static::$table_name . "` 
                         WHERE  `disclaimer_audience_type` = ?
                         AND `disclaimer_audience_value` 
                         IN (" . implode(",", $disclaimer_audience_value) . ")
                         GROUP BY `disclaimer_id`
                         ORDER BY `disclaimer_id` ASC", array($disclaimer_audience_type));
        if ($result) {
            $disclaimers_audience = array();
            foreach ($result as $disclaimer_audience) {
                $self = new self($disclaimer_audience);
                $disclaimers_audience[] = $self;
            }
            return $disclaimers_audience;
        } else {
            return false;
        }
    }

    public function deleteByDisclaimerID($disclaimer_id) {
        global $db;
        if (isset($disclaimer_id)) {
            $query = "DELETE FROM `" . static::$table_name . "` WHERE `disclaimer_id` = ?";

            if ($db->Execute($query, array($disclaimer_id))) {
                return true;
            } else {
                application_log("error", "Failed to Delete Disclaimer Audience with Disclaimer ID[" . $disclaimer_id . "].  DB Said: " . $db->ErrorMsg());
                return false;
            }
        } else {
            return false;
        }
    }

}