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
 * A class to handle course group contacts.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

class Models_Course_Group_Contact extends Models_Base {
    protected $cgcontact_id, $cgroup_id, $proxy_id, $contact_order = 0 , $updated_date, $updated_by;

    protected static $table_name = "course_group_contacts";
    protected static $default_sort_column = "contact_order";
    protected static $primary_key = "cgcontact_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getCgcontactID() {
        return $this->cgcontact_id;
    }

    public function getCgroupID() {
        return $this->cgroup_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getContactOrder() {
        return $this->contact_order;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public static function fetchRowByID($cgcontact_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "cgcontact_id", "value" => $cgcontact_id, "method" => "=", "mode" => "AND")
            )
        );
    }
    
    public static function fetchRowByProxyIDCGroupID($proxy_id, $cgroup_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "proxy_id", "value" => $proxy_id, "method" => "=", "mode" => "AND"),
                array("key" => "cgroup_id", "value" => $cgroup_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllByCgroupID($cgroup_id = 0) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "cgroup_id", "value" => $cgroup_id, "method" => "=", "mode" => "AND")
            )
        );
    }
    
    public static function fetchAllByProxyID($proxy_id = 0) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "proxy_id", "value" => $proxy_id, "method" => "=", "mode" => "AND")
            )
        );
    }
    
    
    public function getExportGroupContactsByGroupID($group_id) {
        global $db;

        $query = "SELECT a.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname` FROM `course_group_contacts` AS a
						JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`cgroup_id` = ?
						ORDER BY a.`contact_order`";
        $course_group_audience = $db->GetAll($query, array($group_id));

        if ($course_group_audience) {
            return $course_group_audience;
        }
        return false;

    }

    public function deleteByGroupID($group_id) {
        global $db;

        $constrains = array($group_id);

        $query = "DELETE FROM `course_group_contacts` WHERE `cgroup_id`= ?";

        $result = $db->Execute($query, $constrains);

        if ($result) {
            return $result;
        }
        return false;

    }
}