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
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Course_Syllabus extends Models_Base {
    protected $syllabus_id, $course_id, $syllabus_start, $syllabus_finish, $template, $repeat, $active;

    protected static $table_name = "course_syllabi";
    protected static $primary_key = "syllabus_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->syllabus_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getSyllabusStart() {
        return $this->syllabus_start;
    }

    public function getSyllabusFinish() {
        return $this->syllabus_finish;
    }

    public function getTemplate() {
        return $this->template;
    }

    public function getRepeate() {
        return $this->repeat;
    }

    public function getActive() {
        return $this->active;
    }

    public function fetchRowByID($syllabus_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "syllabus_id", "value" => $syllabus_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "syllabus_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByCourseID($course_id) {
        $self = new self();
        $constraints = array(
            array("key" => "course_id", "value" => $course_id, "method" => "=")
        );
        return $self->fetchAll($constraints);
    }

}