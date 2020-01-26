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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_ExternalAssessor extends Models_Base {
    protected $eassessor_id, $firstname, $lastname, $email, $created_date, $created_by, $deleted_date, $updated_date, $updated_by;
    
    protected static $table_name = "cbl_external_assessors";
    protected static $primary_key = "eassessor_id";
    protected static $default_sort_column = "eassessor_id";
    
    public function getID () {
        return $this->eassessor_id;
    }

    public function getEmail () {
        return $this->email;
    }

    public function setEmail ($email) {
        $this->email = $email;
    }
    
    public function getFirstname () {
        return $this->firstname;
    }
    
    public function getLastname () {
        return $this->lastname;
    }
    
    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }
    
    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public static function fetchAllBySearchValue ($search_term = "", $deleted_date = null) {
        global $db;
        $query = "  SELECT `eassessor_id`, `firstname`, `lastname`, `email` FROM `cbl_external_assessors` 
                    WHERE `deleted_date` IS NULL 
                    AND 
                        (
                            CONCAT(`firstname`, ' ' , `lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR 
                            CONCAT(`lastname`, ' ' , `firstname`) LIKE ".$db->qstr("%".$search_term."%")." OR 
                            email LIKE ".$db->qstr("%".$search_term."%")."
                        );";
        
        $results = $db->GetAll($query);
        
        return $results;
    }

    public static function fetchAllByDistributionID($adistribution_id) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        );
        return $self->fetchAll($constraints);
    }

    public static function fetchAllByCreatedBy($proxy_id) {
        $self = new self();
        $constraints = array(
            array("key" => "created_by", "value" => $proxy_id, "method" => "=")
        );
        return $self->fetchAll($constraints);
    }

    public static function fetchRowByID($eassessor_id, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "eassessor_id", "value" => $eassessor_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        return $self->fetchRow($constraints);
    }
    
    public static function internalUserExists ($email = null) {
        global $db;
        $user_exists = false;
        
        $query	= "SELECT `firstname`, `lastname`, `email` FROM `".AUTH_DATABASE."`.`user_data` WHERE `email` = ?";
        $result	= $db->GetRow($query, array($email));
        
        if ($result) {
            $user_exists = true;
        }
        
        return $user_exists;
    }
    
    public static function externalUserExists ($email = null) {
        global $db;
        $user_exists = false;
        
        $query	= "SELECT `firstname`, `lastname`, `email` FROM `cbl_external_assessors` WHERE `email` = ?";
        $result	= $db->GetRow($query, array($email));
        
        if ($result) {
            $user_exists = true;
        }
        
        return $user_exists;
    }

    public function delete() {
        global $db;
        if ($db->Execute("DELETE FROM `".static::$table_name."` WHERE `".static::$primary_key."` = ".$this->getID())) {
            return $this;
        } else {
            application_log("error", "Error deleting  ".get_called_class()." id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }

    public function getExternalAssessorsByCourseFacultyMembers($faculty_members) {
        $external_assessors = array();
        if ($faculty_members) {
            foreach ($faculty_members as $faculty_member) {
                $external_assessors_list = Models_Assessments_Distribution_ExternalAssessor::fetchAllByCreatedBy($faculty_member->getProxyID());
                if (!empty($external_assessors_list)) {
                    $external_assessors[] = $external_assessors_list;
                }
            }
        }
        return $external_assessors;
    }

    public function checkExternalAssessorAssociationOwnership($external_faculty_id) {
        $external_found = false;
        $course_contact_model = new Models_Assessments_Distribution_CourseContact();
        $course_list = Models_Course::getActiveUserCoursesIDList();

        foreach ($course_list as $course_id) {
            if ($course_contact_model->fetchRowByAssessorValueAssessorTypeCourseID($external_faculty_id, "external", $course_id)) {
                $external_found = true;
                break;
            }
        }

        return $external_found;
    }

    public function insertExternalAssessorRecord($first_name, $last_name, $email) {
        global $ENTRADA_USER, $translate;
        $external_assessor_id = false;

        $external_assessor = new Models_Assessments_Distribution_ExternalAssessor(array(
                "firstname"     => $first_name,
                "lastname"      => $last_name,
                "email"         => $email,
                "created_date"  => time(),
                "created_by"    => $ENTRADA_USER->getActiveID(),
                "updated_date"  => time(),
                "updated_by"    => $ENTRADA_USER->getActiveID()
            )
        );

        if (!$external_assessor->insert()) {
            add_error($translate->_("An error occurred while attempting to insert an external assessor"));
        } else {
            $external_assessor_id = $external_assessor->getID();
        }

        return $external_assessor_id;
    }
}
