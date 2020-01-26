<?php
/**
 * Models_Secure_RpNowUsers
 *
 * A model for handeling RpNow Users.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */
class Models_Secure_RpNowUsers extends Models_Base {

    protected $rpnow_id, $proxy_id, $exam_code, $ssi_record_locator,
        $rpnow_config_id, $created_date, $created_by,  $updated_date, $updated_by, $deleted_date, $deleted_by;

    protected static $table_name = "rp_now_users";
    protected static $primary_key = "rpnow_id";
    protected static $default_sort_column = "rpnow_id";

    protected $user;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rpnow_id;
    }

    public function getRpnowId()
    {
        return $this->rpnow_id;
    }

    public function getProxyId()
    {
        return $this->proxy_id;
    }

    public function getExamCode()
    {
        return $this->exam_code;
    }

    public function getSsiRecordLocator()
    {
        return $this->ssi_record_locator;
    }

    public function getRpnowConfigId()
    {
        return $this->rpnow_config_id;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function getCreatedBy()
    {
        return $this->created_by;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public function getUpdatedBy()
    {
        return $this->updated_by;
    }

    public function getDeletedBy()
    {
        return $this->deleted_by;
    }

    public function setDeletedDate($value) {
        $this->deleted_date = $value;
    }

    /* @return bool|Models_User */
    public function getUser() {
        if ($this->user === null) {
            return $this->user = Models_User::fetchRowByID($this->proxy_id);
        } else {
            return $this->user;
        }
    }

    /* @return bool|Models_Secure_RpNowUsers */
    public static function fetchRowByID($rpnow_id = NULL, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rpnow_id", "value" => $rpnow_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Secure_RpNowUsers */
    public static function fetchRowByRpnowConfigIdProxyId($rpnow_config_id = NULL, $proxy_id = NULL, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rpnow_config_id", "value" => $rpnow_config_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return bool|Models_Secure_RpNowUsers */
    public static function fetchAllByRpNowConfigID($rpnow_config_id = NULL, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "rpnow_config_id", "value" => $rpnow_config_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function getAllUsersNeedUpdate() {
        global $db;
        $output = array();
        $query = "SELECT a.* FROM `rp_now_users` AS a
                        INNER JOIN `rp_now_config` AS b
                            ON a.`rpnow_config_id` = b.`rpnow_id`
                            AND b.`deleted_date` IS NULL 
                        WHERE (a.`updated_date` < b.`updated_date` OR a.`ssi_record_locator` IS NULL) 
                        AND a.`deleted_date` IS NULL";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Secure_RpNowUsers($result);
            }
        }
        return $output;
    }

    public function generateCode($length) {
        $pool = array_merge(range(0,9),range('A', 'Z'));
        $key = "";

        for($i=0; $i < $length; $i++) {
            $key .= $pool[mt_rand(0, count($pool) - 1)];
        }

        return $key;
    }
}
