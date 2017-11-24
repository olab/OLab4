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

class Models_Course_Report extends Models_Base {
    protected $creport_id, $course_id, $course_report_id, $updated_date, $updated_by;

    protected static $table_name = "course_reports";
    protected static $primary_key = "creport_id";
    protected static $default_sort_column = "creport_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->creport_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCourseReportID() {
        return $this->course_report_id;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public static function fetchRowByID($creport_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "creport_id", "value" => $creport_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "creport_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByCourseID($course_id) {
        $self = new self();
        $constraints = array(
            array("key" => "course_id", "value" => $course_id, "method" => "=")
        );
        return $self->fetchAll($constraints);
    }

    public function getAllReportsByOrganisation($organisation_id) {
        global $db;

        $query = "	SELECT *
                        FROM `course_report_organisations` a
                        JOIN `course_lu_reports` b
                        ON a.`course_report_id` = b.`course_report_id`
                        WHERE a.`organisation_id` = ? ";

        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            return $results;
        }

        return false;
    }

    public function deleteByCourseID($course_id) {
        global $db;

        $query = "DELETE FROM `course_reports` WHERE `course_id`= ? ";
        $result = $db->Execute($query, array($course_id));

        if ($result) {
            return $result;
        }
        return false;
    }

}