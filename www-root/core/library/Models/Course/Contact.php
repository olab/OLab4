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
 * A model for handling course contacts.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Course_Contact extends Models_Base {
    protected $contact_id,
                $course_id,
                $proxy_id,
                $contact_type,
                $contact_order = 0;

    protected static $table_name = "course_contacts";
    protected static $primary_key = "contact_id";
    protected static $default_sort_column = "contact_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->contact_id;
    }

    public function getContactID() {
        return $this->contact_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getContactType() {
        return $this->contact_type;
    }

    public function getContactOrder() {
        return $this->contact_order;
    }

    public static function fetchRowByID($contact_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "contact_id", "value" => $contact_id, "method" => "=")
        ));
    }
    
    public static function fetchRowByContactID($contact_id = 0) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "contact_id", "value" => $contact_id, "method" => "=", "mode" => "AND")
            )
        );
    }
    
    /* @return bool|Models_Course_Contact */
    public static function fetchAllByCourseID($course_id = 0, $deleted_date = NULL) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "course_id",
                "value"     => $course_id,
                "method"    => "="
            ),
            array("key" => "deleted_date",
                "value" => ($deleted_date ? $deleted_date : NULL),
                "method" => ($deleted_date ? "<=" : "IS")
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }


    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "contact_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByCourseIDContactType($course_id, $contact_type = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "course_id", "value" => $course_id, "method" => "=")
        );
        if ($contact_type) {
            $constraints[] = array("key" => "contact_type", "value" => $contact_type, "method" => "=");
        }
        return $self->fetchAll($constraints);
    }

    public static function fetchRowByProxyIDContactType($proxy_id, $contact_type = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "=")
        );
        if ($contact_type) {
            $constraints[] = array("key" => "contact_type", "value" => $contact_type, "method" => "=");
        }
        return $self->fetchRow($constraints);
    }

    public function getApproverName($proxy_id = "") {
        $name = false;
        $user = Models_User::fetchRowByID($proxy_id == "" ? $this->proxy_id : $proxy_id);

        if ($user) {
            $name = $user->getFirstname() . " " . $user->getLastname();
        }
        return $name;
    }

    public static function fetchApproversByCourseIDSearchTermContactType($course_id, $search_value = NULL, $contact_type = NULL) {
        global $db;

        $constraints = array($course_id);
        $search_sql = "";
        if ($search_value) {
            $search_sql = " AND t2.firstname LIKE ? 
                            OR t2.lastname LIKE ?";
            $constraints = array_merge($constraints, array("%".$search_value."%", "%".$search_value."%"));
        }

        $contact_type_sql = "";
        if ($contact_type) {
            $contact_type_sql = " AND contact_type = ?";
            $constraints[] = $contact_type;
        }

        $query = "SELECT DISTINCT t1.proxy_id, t2.firstname, t2.lastname
                  FROM `".DATABASE_NAME."`.`course_contacts` t1
                  JOIN `".AUTH_DATABASE."`.`user_data` t2 ON t1.proxy_id = t2.id
                  WHERE course_id = ?
                  ".$search_sql."
                  ".$contact_type_sql;

        $results = $db->getAll($query, $constraints);

        return $results;
    }

    public function deleteByCourseID($course_id) {
        global $db;

        $constrains = array($course_id);
        
        $query = "DELETE FROM `course_contacts` WHERE `course_id`= ?";

        $result = $db->Execute($query, $constrains);

        if ($result) {
            return $result;
        }
        return false;
    }

    public static function fetchByProxyAndCourse($proxy_id, $course_id) {
        $self = new self();

        $constraints = array(
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "=")
        );

        return $self->fetchAll($constraints);
    }
}