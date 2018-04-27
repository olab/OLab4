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
 * A model for Medbiquitous Resources
 *
 * @author Organisation: Queen's University
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Medbiq_Resource extends Models_Base {

    protected $resource_id;
    protected $resource;
    protected $resource_description;
    protected $active;
    protected $updated_date;
    protected $updated_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "medbiq_resources";
    protected static $primary_key = "resource_id";
    protected static $default_sort_column = "resource";

    /**
     * Models_Medbiq_Resource constructor.
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
        return $this->resource_id;
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
     * @return string
     */
    public function getResource() {
        return $this->resource;
    }

    /**
     * @param string $resource
     */
    public function setResource($resource) {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getResourceDescription() {
        return $this->resource_description;
    }

    /**
     * @param string $resource_description
     */
    public function setResourceDescription($resource_description) {
        $this->resource_description = $resource_description;
    }

    /**
     * @return integer
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param integer $active
     */
    public function setActive($active) {
        $this->active = $active;
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
     * @param integer $resource_id
     * @param integer $active
     *
     * @return bool|Models_Medbiq_Resource
     */
    public static function fetchRowByID($resource_id, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "resource_id", "method" => "=", "value" => $resource_id),
            array("key" => "active", "method" => "=", "value" => $active)
        ));
    }

    /**
     * @param string $resource
     * @param integer $active
     *
     * @return bool|Models_Medbiq_Resource
     */
    public static function fetchRowByResource($resource_title, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "resource", "method" => "=", "value" => $resource_title),
            array("key" => "active", "method" => "=", "value" => $active)
        ));
    }

    /**
     * @param string $resource_title
     * @param integer $active
     *
     * @return array
     */
    public static function fetchAllRecords($resource_title = null, $active = 1) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "active", "method" => "=", "value" => $active),
            array("key" => "resource", "method" => "LIKE", "value" => "%".$resource_title."%")
        ));
    }
}