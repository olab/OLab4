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
 * A model for handling User Access Requests
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_User_Access_Request extends Models_Base {

    protected $user_access_request_id;
    protected $receiving_proxy_id;
    protected $requested_user_firstname;
    protected $requested_user_lastname;
    protected $requested_user_email;
    protected $requested_user_number;
    protected $requested_group;
    protected $requested_role;
    protected $additional_comments;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "user_access_requests";
    protected static $primary_key = "user_access_request_id";
    protected static $default_sort_column = "user_access_request_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->user_access_request_id;
    }

    public function getUserAccessRequestID() {
        return $this->user_access_request_id;
    }

    public function setUserAccessRequestID($user_access_request_id) {
        $this->user_access_request_id = $user_access_request_id;

        return $this;
    }

    public function getReceivingProxyID() {
        return $this->receiving_proxy_id;
    }

    public function setReceivingProxyID($receiving_proxy_id) {
        $this->receiving_proxy_id = $receiving_proxy_id;

        return $this;
    }

    public function getRequestedUserFirstname() {
        return $this->requested_user_firstname;
    }

    public function setRequestedUserFirstname($requested_user_firstname) {
        $this->requested_user_firstname = $requested_user_firstname;

        return $this;
    }

    public function getRequestedUserLastname() {
        return $this->requested_user_lastname;
    }

    public function setRequestedUserLastname($requested_user_lastname) {
        $this->requested_user_lastname = $requested_user_lastname;

        return $this;
    }

    public function getRequestedUserEmail() {
        return $this->requested_user_email;
    }

    public function setRequestedUserEmail($requested_user_email) {
        $this->requested_user_email = $requested_user_email;

        return $this;
    }

    public function getRequestedUserNumber() {
        return $this->requested_user_number;
    }

    public function setRequestedUserNumber($requested_user_number) {
        $this->requested_user_number = $requested_user_number;

        return $this;
    }

    public function getRequestedGroup() {
        return $this->requested_group;
    }

    public function setRequestedGroup($requested_group) {
        $this->requested_group = $requested_group;

        return $this;
    }

    public function getRequestedRole() {
        return $this->requested_role;
    }

    public function setRequestedRole($requested_role) {
        $this->requested_role = $requested_role;

        return $this;
    }

    public function getAdditionalComments() {
        return $this->additional_comments;
    }

    public function setAdditionalComments($additional_comments) {
        $this->additional_comments = $additional_comments;

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

    public static function fetchRowByID($user_access_request_id = 0, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "user_access_request_id", "method" => "=", "value" => $user_access_request_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    /**
     * Fetch a row by the provided receiving_proxy_id, requested_user_email, requested_group and requested_role
     *
     * @param int $proxy_id
     * @param string $email
     * @param string $group
     * @param string $role
     * @return array
     */
    public function fetchRowByProxyIDEmailGroupRole($proxy_id = 0, $email = "", $group = "", $role = "", $deleted_date = NULL)  {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "receiving_proxy_id", "method" => "=", "value" => $proxy_id),
            array("key" => "requested_user_email", "method" => "=", "value" => $email),
            array("key" => "requested_group", "method" => "=", "value" => $group),
            array("key" => "requested_role", "method" => "=", "value" => $role),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}