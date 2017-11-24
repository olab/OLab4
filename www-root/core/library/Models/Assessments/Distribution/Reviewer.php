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
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Reviewer extends Models_Base {
    protected $adreviewer_id, $adistribution_id, $proxy_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_distribution_reviewers";
    protected static $primary_key = "adreviewer_id";
    protected static $default_sort_column = "adreviewer_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adreviewer_id;
    }

    public function getAdreviewerID() {
        return $this->adreviewer_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->created_date;
    }

    /**
     * @return mixed
     */
    public function getDeletedDate()
    {
        return $this->deleted_date;
    }

    /**
     * @return mixed
     */
    public function getUpdatedBy()
    {
        return $this->updated_by;
    }

    /**
     * @return mixed
     */
    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public static function fetchRowByID($adreviewer_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adreviewer_id", "value" => $adreviewer_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adreviewer_id", "value" => 0, "method" => ">=")));
    }
    
    public function getReviewerName() {
        $return = false;
        $fullname = get_account_data("fullname", $this->proxy_id);
        if ($fullname) {
            $return = $fullname;
        }

        return $return;
    }

    public static function fetchRowByProxyID($proxy_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "=")
        ));
    }

    public static function fetchAllByProxyID($proxy_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "proxy_id", "value" => $proxy_id, "method" => ">=")));
    }

    public static function fetchAllByDistributionID ($adistribution_id, $proxy_id = NULL, $deleted_date = NULL) {
        $self = new self();
        $params = array(array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="));
        if (!is_null($deleted_date)) {
            $params[] = array("key" => "deleted_date", "value" => $deleted_date, "method" => "<=");
        } else {
            $params[] = array("key" => "deleted_date", "value" => NULL, "method" => "IS");
        }
        if (!is_null($proxy_id)) {
            $params[] = array("key" => "proxy_id", "value" => $proxy_id, "method" => "=");
        }
        return $self->fetchAll($params);
    }

    public function delete() {
        global $ENTRADA_USER;
        $this->deleted_date = time();
        $this->updated_date = time();
        $this->updated_by = (isset($ENTRADA_USER) && $ENTRADA_USER ? $ENTRADA_USER->getID() : 0);
        return $this->update();
    }

}