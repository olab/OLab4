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
 * A model for handling the users approval or decline of User Disclaimers
 *
 * @author Organisation: Queens University
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_Disclaimer_Audience_Users extends Models_Base {

    protected $disclaimer_audience_users_id;
    protected $disclaimer_id;
    protected $proxy_id;
    protected $approved;
    protected $updated_date;
    protected $updated_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "disclaimer_audience_users";
    protected static $primary_key = "disclaimer_audience_users_id";
    protected static $default_sort_column = "disclaimer_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->disclaimer_audience_users_id;
    }

    public function getDisclaimerAudienceUsersID() {
        return $this->disclaimer_audience_users_id;
    }

    public function setDisclaimerAudienceUsersID($disclaimer_audience_users_id) {
        $this->disclaimer_audience_users_id = $disclaimer_audience_users_id;
    }

    public function getDisclaimerID() {
        return $this->disclaimer_id;
    }

    public function setDisclaimerID($disclaimer_id) {
        $this->disclaimer_id = $disclaimer_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function setProxyID($proxy_id) {
        $this->proxy_id = $proxy_id;
    }

    public function getApproved() {
        return $this->approved;
    }

    public function setApproved($approved) {
        $this->approved = $approved;
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

    public static function fetchRowByID($disclaimer_audience_users_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_audience_users_id", "method" => "=", "value" => $disclaimer_audience_users_id)
        ));
    }

    public static function fetchRowByDisclaimerIDProxyID($disclaimer_id, $proxy_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id),
            array("key" => "proxy_id", "method" => "=", "value" => $proxy_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "disclaimer_audience_users_id", "method" => ">=", "value" => 0)));
    }

    public static function fetchAllByDisclaimerID($disclaimer_id, $approved = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id),
            (isset($approved) ? array("key" => "approved", "method" => "=", "value" => $approved) : false)
        ));
    }

}