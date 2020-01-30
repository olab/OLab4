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
 * Mapping from Events to Meqbiq Resources
 *
 * @author Organisation: Queen's University
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Event_Medbiq_Resource extends Models_Base {

    protected $em_resource_id;
    protected $event_id;
    protected $resource_id;
    protected $updated_date;
    protected $updated_by;
    protected $created_date;
    protected $created_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "event_medbiq_resources";
    protected static $primary_key = "em_resource_id";
    protected static $default_sort_column = "event_id";

    /**
     * Models_Event_Medbiq_Resources constructor.
     *
     * @param null $arr
     */
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    /**
     * @return integer
     */
    public function getID() {
        return $this->em_resource_id;
    }

    /**
     * @return integer
     */
    public function getEmResourceID() {
        return $this->em_resource_id;
    }

    /**
     * @param integer $em_resource_id
     */
    public function setEmResourceID($em_resource_id) {
        $this->em_resource_id = $em_resource_id;
    }

    /**
     * @return integer
     */
    public function getEventID() {
        return $this->event_id;
    }

    /**
     * @param integer $event_id
     */
    public function setEventID($event_id) {
        $this->event_id = $event_id;
    }

    /**
     * @return integer
     */
    public function getResourceID() {
        return $this->resource_id;
    }

    /**
     * @param integer $resource_id
     */
    public function setResourceID($resource_id) {
        $this->resource_id = $resource_id;
    }

    /**
     * @return integer
     */
    public function getUpdatedDate() {
        return $this->updated_date;
    }

    /**
     * @param integer $updated_date
     */
    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    /**
     * @return integer
     */
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    /**
     * @param integer $updated_by
     */
    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    /**
     * @return integer
     */
    public function getCreatedDate() {
        return $this->created_date;
    }

    /**
     * @param integer $created_date
     */
    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    /**
     * @return integer
     */
    public function getCreatedBy() {
        return $this->created_by;
    }

    /**
     * @param integer $created_by
     */
    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    /**
     * @return integer
     */
    public function getDeletedDate() {
        return $this->deleted_date;
    }

    /**
     * @param integer $deleted_date
     */
    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    /**
     * @param integer $em_resource_id
     *
     * @return bool|Models_Event_Medbiq_Resources
     */
    public static function fetchRowByID($em_resource_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "em_resource_id", "method" => "=", "value" => $em_resource_id)
        ));
    }

    /**
     * @param integer $event_id
     * @param integer $resource_id
     * @param integer $deleted_date
     *
     * @return bool|Models_Event_Medbiq_Resources
     */
    public static function fetchRowByEventIDResourceID($event_id, $resource_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "method" => "=", "value" => $event_id),
            array("key" => "resource_id", "method" => "=", "value" => $resource_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /**
     * @return null|array
     */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "em_resource_id", "method" => ">=", "value" => 0),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /**
     * @return null|array
     */
    public static function fetchAllRecordsByEventId($event_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "event_id", "method" => "=", "value" => $event_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /**
     * @return $this|bool
     */
    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

}