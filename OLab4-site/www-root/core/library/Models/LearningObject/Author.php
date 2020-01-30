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
 * Model for a learning object author.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Models_LearningObject_Author extends Models_Base
{
    protected $id, $author_id, $author_type, $learning_object_id;

    protected static $table_name = "learning_object_authors";
    protected static $primary_key = "id";
    protected static $default_sort_column = "id";

    public function __construct($arr = NULL)
    {
        parent::__construct($arr);
    }

    public function getID()
    {
        return $this->id;
    }

    public function getAuthorID()
    {
        return $this->author_id;
    }

    public function getAuthorType()
    {
        return $this->author_type;
    }

    public function getLearningResourceID()
    {
        return $this->learning_object_id;
    }

    public static function fetchRowByID($id)
    {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "=")
        ));
    }

    public static function fetchAllRecords()
    {
        $self = new self();
        return $self->fetchAll(array(array("key" => "id", "value" => 0, "method" => ">=")));
    }

    public static function getAuthorName($author_id, $author_type)
    {
        $return = false;
        switch (strtolower($author_type)) {
            case "internal" :
                $user = User::fetchRowByID($author_id);
                if ($user) {
                    $return = $user->getFullname(false);
                }
                break;
            case "external" :
                $user = Models_LearningObject_ExternalAuthor::fetchRowByID($author_id);
                if ($user) {
                    $return = $user->getFullname();
                }
                break;
            default :
                $return = false;
                break;
        }
        return $return;
    }

    public static function fetchAllByLearningResourceID($learning_object_id, $author_id = NULL, $author_type = NULL)
    {
        $self = new self();
        $params = array(array("key" => "learning_object_id", "value" => $learning_object_id, "method" => "="));
        if (!is_null($author_id) && !is_null($author_type)) {
            $params[] = array("key" => "author_id", "value" => $author_id, "method" => "=");
            $params[] = array("key" => "author_type", "value" => $author_type, "method" => "=");
        }
        return $self->fetchAll($params);
    }

    public static function deleteAllByLearningResourceID($learning_object_id)
    {
        global $db;

        $learning_object_id = (int)$learning_object_id;

        if ($learning_object_id && $db->Execute("DELETE FROM `learning_object_authors` WHERE `learning_object_id` = " . $learning_object_id)) {
            return true;
        }

        application_log("error", "Error deleting " . get_called_class() . " learning_object_id[" . $learning_object_id . "]. DB Said: " . $db->ErrorMsg());
        return false;
    }
}