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
 * Model for a learning object external author.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Models_LearningObject_ExternalAuthor extends Models_Base
{
    protected $eauthor_id, $firstname, $lastname, $email, $created_date, $created_by, $deleted_date, $updated_date, $updated_by;

    protected static $table_name = "learning_object_external_authors";
    protected static $primary_key = "eauthor_id";
    protected static $default_sort_column = "eauthor_id";

    public function getID()
    {
        return $this->eauthor_id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function getFullname()
    {
        return $this->firstname . " " . $this->lastname;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function getCreatedBy()
    {
        return $this->created_by;
    }

    public function getDeletedDate()
    {
        return $this->deleted_date;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public function getUpdatedBy()
    {
        return $this->updated_by;
    }

    public static function fetchAllBySearchValue($search_term = "", $deleted_date = null)
    {
        global $db;
        $query = "SELECT `eauthor_id`, `firstname`, `lastname`, `email`
                  FROM `learning_object_external_authors`
                  WHERE `deleted_date` IS NULL
                  AND (
                      CONCAT(`firstname`, ' ' , `lastname`) LIKE " . $db->qstr("%" . $search_term . "%") . " OR
                      CONCAT(`lastname`, ' ' , `firstname`) LIKE " . $db->qstr("%" . $search_term . "%") . " OR
                      `email` LIKE " . $db->qstr("%" . $search_term . "%") . "
                  )";

        $results = $db->GetAll($query);

        return $results;
    }

    public static function fetchRowByID($eauthor_id)
    {
        $self = new self();
        $constraints = array(
            array("key" => "eauthor_id", "value" => $eauthor_id, "method" => "=")
        );
        return $self->fetchRow($constraints);
    }

    public static function internalUserExists($email = null)
    {
        global $db;
        $user_exists = false;

        $query = "SELECT `firstname`, `lastname`, `email` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE `email` = ?";
        $result = $db->GetRow($query, array($email));

        if ($result) {
            $user_exists = true;
        }

        return $user_exists;
    }

    public static function externalUserExists($email = null)
    {
        global $db;
        $user_exists = false;

        $query = "SELECT `firstname`, `lastname`, `email` FROM `learning_object_external_authors` WHERE `email` = ?";
        $result = $db->GetRow($query, array($email));

        if ($result) {
            $user_exists = true;
        }

        return $user_exists;
    }

    public function delete()
    {
        global $db;

        if ($db->Execute("DELETE FROM `" . $this->table_name . "` WHERE `" . $this->primary_key . "` = " . $this->getID())) {
            return $this;
        } else {
            application_log("error", "Error deleting  " . get_called_class() . " id[" . $this->{$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }
}
