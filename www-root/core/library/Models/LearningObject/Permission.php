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
 * A model to handle learning object tags.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_LearningObject_Permission extends Models_Base {

    protected $lo_file_permission_id, $lo_file_id, $proxy_id, $permission, 
              $updated_date, $updated_by, $active = 1;
    
    protected static $table_name = "learning_object_files";
    protected static $default_sort_column = "filename";
    protected static $primary_key = "lo_file_permission_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($lor_file_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "lor_file_id", "value" => $lor_file_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecordsByProxyID($proxy_id, $active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "proxy_id",
                "value"     => $proxy_id,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "active",
                "value"     => $active,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function fetchAllRecordsByFileID($lo_file_id, $active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "lo_file_id",
                "value"     => $lo_file_id,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "active",
                "value"     => $active,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
    
    public function getLoFilePermissionID() {
        return $this->lo_file_permission_id;
    }

    public function getLoFileID() {
        return $this->lo_file_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getPermission() {
        return $this->permission;
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

}

?>
