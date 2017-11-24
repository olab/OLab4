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

class Models_Course_Keyword extends Models_Base {
    protected $ckeyword_id, $course_id, $keyword_id, $updated_date, $updated_by;

    protected static $table_name = "course_keywords";
    protected static $primary_key = "ckeyword_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->ckeyword_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getKeywordID() {
        return $this->keyword_id;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public static function fetchRowByID($ckeyword_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "ckeyword_id", "value" => $ckeyword_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "ckeyword_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByCourseID($course_id) {
        $self = new self();
        $constraints = array(
            array("key" => "course_id", "value" => $course_id, "method" => "=")
        );
        return $self->fetchAll($constraints);
    }

    public function deleteByCourseIDKeywordID($course_id, $keyword_id) {
        global $db;

        $query = "  DELETE FROM `course_keywords` WHERE course_id = ? AND  keyword_id = ?";
        if ($db->Execute($query, array($course_id, $keyword_id))) {
            return true;
        }
        return false;

    }

    public function getAllDescriptorsByCourseID($course_id) {
        global $db;

        $query = "SELECT ck.`keyword_id`, d.`descriptor_name`
                                                    FROM `course_keywords` AS ck
                                                    JOIN `mesh_descriptors` AS d
                                                    ON ck.`keyword_id` = d.`descriptor_ui`
                                                    AND ck.`course_id` = ?
                                                    ORDER BY `descriptor_name`";
        $results = $db->getAll($query, array($course_id));

        if ($results) {
            return $results;
        }

        return false;

    }

}