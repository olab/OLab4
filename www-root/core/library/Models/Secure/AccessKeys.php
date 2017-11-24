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
 * A model to access SEB browser keys for secure resources
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 */
class Models_Secure_AccessKeys extends Models_Base {

    protected $id, $resource_type, $resource_id, $key, $version, $updated_date, $updated_by, $deleted_date;
    
    protected static $table_name = "secure_access_keys";
    protected static $primary_key = "id";
    protected static $default_sort_column = "id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID ($id = null) {
        $self = new self();
        
        $constraints = array (
            array(
                "key" => "id",
                "value" => $id,
                "method" => "="
            ),
        );
        
        return $self->fetchRow($constraints);
    }
    
    public static function fetchAllByResourceTypeResourceID ($resource_type, $resource_id = null, $deleted_date = NULL) {
        $self = new self();
        
        $constraints = array (
            array(
                "key" => "resource_type",
                "value" => $resource_type,
                "method" => "="
            ),
            array(
                "mode" => "AND",
                "key" => "resource_id",
                "value" => $resource_id,
                "method" => "="
            ),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        
        return $self->fetchAll($constraints);
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function getResourceType() {
        return $this->resource_type;
    }

    public function getResourceID() {
        return $this->resource_id;
    }

    public function getKey() {
        return $this->key;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public function getActive() {
        return $this->active;
    }

    public function delete() {
        $this->deleted_date = time();
        $this->updated_date = time();

        return $this->update();
    }
    
}