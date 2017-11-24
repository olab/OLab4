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
 * A model for handling objectives associated with gradebook assessments.
 *
 * @author Organisation: Queen's University
 * @author Developer: Steve Yang <sy49@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Collection extends Models_Base {
    protected $collection_id, $course_id, $title, $description, $active = 1; //column order is not used; may be dropped from the table in db

    protected static $table_name = "assessment_collections";
    protected static $primary_key = "collection_id";
    protected static $default_sort_column = "title";
    

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->collection_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function setCourseID($course_id) {
        $this->course_id = $course_id;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

     public function getActive() {
        return $this->active;
    }
    /**
     * Set the active flag. 
     * @param int $active 1|0
     */
    public function setActive($active) {
        $this->active = $active;
    }

    public function fetchRowByTitle($collection_title, $course_id, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "title", "value" => $collection_title, "method" => "=", "mode" => "AND"),
            array("key" => "course_id", "value" => $course_id, "method" => "=", "mode" => "AND"),
            array("key" => "active", "value" => $active, "method" => "=", "mode" => "AND")
        ));
    }

    public static function fetchRowByID($collection_id, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "collection_id", "value" => $collection_id, "method" => "=", "mode" => "AND"),
            array("key" => "active", "value" => $active, "method" => "=", "mode" => "AND")
        ));
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->collection_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }
    
    public function update() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`collection_id` = ".$this->collection_id)) {
            return $this;
        } else {
            return false;
        }
    }
    
    public static function fetchAllRows() {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "collection_id", "value" => 0, "method" => ">", "mode" => "AND"),
            array("key" => "active", "value" => 1, "method" => "=", "mode" => "AND")
        ));
    }

    public static function fetchAllRowsByCourseID($course_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "collection_id", "value" => 0, "method" => ">", "mode" => "AND"),
            array("key" => "course_id", "value" => $course_id, "method" => "=", "mode" => "AND"),
            array("key" => "active", "value" => 1, "method" => "=", "mode" => "AND")
        ));
    }
}