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
 * A model for handling the access records associated with users.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_User_Access extends Models_Base {
    const TABLE_NAME = "user_access";
    protected $id, $user_id, $app_id, $organisation_id, $account_active, $access_starts, $access_expires, $last_login, $last_ip, $login_attempts, $locked_out_until, $role, $group, $extras, $private_hash, $notes;

    protected static $database_name = AUTH_DATABASE;
    protected static $table_name = "user_access";
    protected static $primary_key = "id";
    protected static $default_sort_column = "id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

    public function getUserID() {
        return $this->user_id;
    }

    public function getAppID() {
        return $this->app_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getAccountActive() {
        return $this->account_active;
    }

    public function getAccessStarts() {
        return $this->access_starts;
    }

    public function getAccessExpires() {
        return $this->access_expires;
    }

    public function getLastLogin() {
        return $this->last_login;
    }

    public function getLastIp() {
        return $this->last_ip;
    }

    public function getLoginAttempts() {
        return $this->login_attempts;
    }

    public function getLockedOutUntil() {
        return $this->locked_out_until;
    }

    public function getRole() {
        return $this->role;
    }

    public function getGroup() {
        return $this->group;
    }

    public function getExtras() {
        return $this->extras;
    }

    public function getPrivateHash() {
        return $this->private_hash;
    }

    public function getNotes() {
        return $this->notes;
    }

    /* @return bool|Models_User_Access */
    public function fetchRowByUserIDOrganisationIDGroup($account_active = "true") {
        return $this->fetchRow(array(
            array("key" => "user_id", "value" => $this->user_id, "method" => "="),
            array("key" => "organisation_id", "value" => $this->organisation_id, "method" => "="),
            array("key" => "group", "value" => $this->group, "method" => "="),
            array("key" => "account_active", "value" => $account_active, "method" => "=")
        ));
    }

    /* @return bool|Models_User_Access */
    public static function fetchRowByID($id, $account_active = "true") {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "="),
            array("key" => "account_active", "value" => $account_active, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_User_Access[] */
    public static function fetchAllByUserIDOrganisationID ($proxy_id, $organisation_id, $account_active = "true") {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "user_id", "value" => $proxy_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "account_active", "value" => $account_active, "method" => "=")
        ));
    }

    /* @return bool|Models_User_Access */
    public static function fetchRowByUserIDOrganisationIDRoleGroup($user_id, $organisation_id, $role, $group, $active = "true") {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "user_id", "value" => $user_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "role", "value" => $role, "method" => "="),
            array("key" => "group", "value" => $group, "method" => "="),
            array("key" => "account_active", "value" => $active, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_User_Access[] */
    public static function fetchAllRecords($account_active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "account_active", "value" => $account_active, "method" => "=")));
    }

    public static function updateHash($new_private_hash, $user_id, $organisation_id) {
        global $db;

        $query = "UPDATE IGNORE `".AUTH_DATABASE."`.`user_access` SET `private_hash` = ? WHERE `user_id` = ? AND `organisation_id` = ? ";

        $result = $db->Execute($query, array($new_private_hash, $user_id, $organisation_id));

        if ($result) {
            return $result;
        }

        return false;

    }

    /* @return ArrayObject|Models_User_Access[] */
    public static function fetchAllByUserID ($proxy_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "user_id", "value" => $proxy_id, "method" => "="),
        ));
    }

    /* @return bool|Models_User_Access */
    public static function fetchRowByUserIDAppID($user_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "user_id", "value" => $user_id, "method" => "="),
            array("key" => "app_id", "value" => AUTH_APP_ID, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_User_Access[] */
    public static function fetchAllByUserIDAppID($user_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "user_id", "value" => $user_id, "method" => "="),
            array("key" => "app_id", "value" => AUTH_APP_ID, "method" => "=")
        ));
    }

    public static function getGroupRoleMembers($organisation_id, $group_name, $role_name) {
        global $db;

        $query	= "	SELECT a.`id`, a.`number`, a.`firstname`, a.`lastname`, a.`username`, a.`email`, a.`organisation_id`, b.`group`, b.`role`
                    FROM `".static::$database_name."`.`user_data` AS a
                    LEFT JOIN `".static::$database_name."`.`user_access` AS b
                    ON a.`id` = b.`user_id` 
                    WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
                    AND b.`organisation_id` = ?
                    AND b.`group` = ?
                    AND b.`role` = ?
                    GROUP BY a.`id`
                    ORDER BY a.`lastname` ASC, a.`firstname` ASC";

        return $db->GetAll($query, array($organisation_id, $group_name, $role_name));
    }

    /**
     * Returns all valid user_access records for the given user (proxy_id).
     * The user must be active, and the current date must be in the range of the start and expiry dates
     *
     * @param $user_id
     * @param $app_id
     * @return bool|array
     */
    public static function fetchAllActiveByProxyIDAppID($proxy_id, $app_id) {
        global $db;

        $proxy_id = (int) $proxy_id;
        $app_id = (int) $app_id;

        $query =   "SELECT * FROM `".static::$database_name."`.`".static::$table_name."` 
                    WHERE `user_id` = ?
                    AND `app_id` = ?
                    AND `account_active` = 'true'
                    AND (`access_starts` = '0' OR `access_starts` < ?)
                    AND (`access_expires` = '0' OR `access_expires` > ?)";

        return $db->GetAll($query, array($proxy_id, $app_id, time(), time()));
    }
}