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
 * A model for handling User Disclaimers
 *
 * @author Organisation: Queens University
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_Disclaimers extends Models_Base {

    protected $disclaimer_id;
    protected $disclaimer_title;
    protected $disclaimer_issue_date;
    protected $disclaimer_expire_date;
    protected $disclaimer_text;
    protected $organisation_id;
    protected $upon_decline;
    protected $trigger_type;
    protected $email_admin;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;
    protected $deleted_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "disclaimers";
    protected static $primary_key = "disclaimer_id";
    protected static $default_sort_column = "disclaimer_title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->disclaimer_id;
    }

    public function getDisclaimerID() {
        return $this->disclaimer_id;
    }

    public function setDisclaimerID($disclaimer_id) {
        $this->disclaimer_id = $disclaimer_id;
    }

    public function getDisclaimerTitle() {
        return $this->disclaimer_title;
    }

    public function setDisclaimerTitle($disclaimer_title) {
        $this->disclaimer_title = $disclaimer_title;
    }

    public function getDisclaimerIssueDate() {
        return $this->disclaimer_issue_date;
    }

    public function setDisclaimerIssueDate($disclaimer_issue_date) {
        $this->disclaimer_issue_date = $disclaimer_issue_date;
    }

    public function getDisclaimerExpireDate() {
        return $this->disclaimer_expire_date;
    }

    public function setDisclaimerExpireDate($disclaimer_expire_date) {
        $this->disclaimer_expire_date = $disclaimer_expire_date;
    }

    public function getDisclaimerText() {
        return $this->disclaimer_text;
    }

    public function setDisclaimerText($disclaimer_text) {
        $this->disclaimer_text = $disclaimer_text;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function setOrganisationID($organisation_id) {
        $this->organisation_id = $organisation_id;
    }

    public function getUponDecline() {
        return $this->upon_decline;
    }

    public function setUponDecline($upon_decline) {
        $this->upon_decline = $upon_decline;
    }

    public function getTriggerType() {
        return $this->trigger_type;
    }

    public function setTriggerType($trigger_type) {
        $this->trigger_type = $trigger_type;
    }

    public function getEmailAdmin() {
        return $this->email_admin;
    }

    public function setEmailAdmin($email_admin) {
        $this->email_admin = $email_admin;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
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

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;
    }

    public static function fetchRowByID($disclaimer_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id)
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "disclaimer_id", "method" => ">=", "value" => 0),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByOrganisationID($organisation_id = NULL, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "disclaimer_id", "method" => ">=", "value" => 0),
            array("key" => "organisation_id", "method" => "=", "value" => $organisation_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function getUserDisclaimerByProxyIDOrganisationID($proxy_id = 0, $organisation_id = 0) {
        global $db;
        $query = "SELECT r.`id` 
                  FROM " . AUTH_DATABASE . ".`user_access` u 
                  INNER JOIN " . AUTH_DATABASE . ".`system_groups` g 
                    ON g.`group_name` = u.`group`
                  INNER JOIN " . AUTH_DATABASE . ".`system_group_organisation` go 
                    ON g.`id` = go.`groups_id` AND go.`organisation_id` = ?
                  INNER JOIN " . AUTH_DATABASE . ".`system_roles` r 
                    ON r.`role_name` = u.`role` AND r.`groups_id` = g.`id` 
                  WHERE u.`user_id` = ? 
                    AND u.`app_id` = " . AUTH_APP_ID . " 
                    AND u.`account_active` = 'true' 
                    AND u.`organisation_id` = ?";
        $results = $db->GetAll($query, [$organisation_id, $proxy_id, $organisation_id]);
        if ($results) {
            $role_ids = Array();
            foreach ($results as $result) {
                $role_ids[] = $result["id"];
            }
            $disclaimers_audience = Models_Disclaimer_Audience::fetchAllByAudienceTypeAudienceValue("role_id", $role_ids);
            if ($disclaimers_audience) {
                $disclaimers = array();
                foreach ($disclaimers_audience as $disclaimer_audience) {
                    $result = $db->GetRow(" SELECT d.*
                                            FROM " . static::$database_name . ".`disclaimers` d
                                            LEFT JOIN " . static::$database_name . ".`disclaimer_audience_users` u
                                                ON u.`disclaimer_id` = d.`disclaimer_id` AND u.`proxy_id` = ?
                                            LEFT JOIN " . static::$database_name . ".`disclaimer_trigger` t
                                                ON t.`disclaimer_id` = d.`disclaimer_id`
                                            WHERE d.`disclaimer_id` = ?
                                                AND d.`organisation_id` = ?
                                                AND d.`deleted_date` IS NULL
                                                AND (u.`disclaimer_audience_users_id` IS NULL OR (u.`approved` = 0 AND (d.`upon_decline` = 'log_out' OR d.`upon_decline` = 'deny_access')))",
                        array($proxy_id, $disclaimer_audience->getDisclaimerID(), $organisation_id));
                    if ($result) {
                        if ($result["disclaimer_issue_date"] == NULL) {
                           if ($result["disclaimer_expire_date"] == NULL) {
                               $disclaimers[] = $result;
                           } else if ($result["disclaimer_expire_date"] > time()) {
                               $disclaimers[] = $result;
                           }
                        } else if ($result["disclaimer_issue_date"] <= time()) {
                            if ($result["disclaimer_expire_date"] == NULL) {
                                $disclaimers[] = $result;
                            } else if ($result["disclaimer_expire_date"] > time()) {
                                $disclaimers[] = $result;
                            }
                        }
                    }
                }
                if (!empty($disclaimers)) {
                    return $disclaimers;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function delete() {
        global $ENTRADA_USER;

        $this->deleted_date = time();
        $this->deleted_by = $ENTRADA_USER->getActiveId();
        $this->updated_date = time();
        $this->updated_by = $ENTRADA_USER->getActiveId();
        $disclaimer_audience = new Models_Disclaimer_Audience();
        $disclaimer_audience->deleteByDisclaimerID($this->disclaimer_id);
        return $this->update();
    }
}