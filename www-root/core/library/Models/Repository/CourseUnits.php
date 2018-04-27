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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Models_Repository_CourseUnits extends Models_Repository implements Models_Repository_ICourseUnits, Entrada_IGettable {

    use Entrada_Gettable;

    public function fetchAllByIDs(array $cunit_ids) {
        global $db;
        if ($cunit_ids) {
            $query = "SELECT *
                      FROM `course_units`
                      WHERE `cunit_id` IN (".$this->quoteIDs($cunit_ids).")
                      AND `deleted_date` IS NULL
                      ORDER BY `unit_order` ASC";
            $results = $db->GetAll($query);
            return $this->fromArrays($results);
        } else {
            return array();
        }
    }

    public function fetchAllByCourseID($course_id) {
        $course_units_by_course = $this->fetchAllByCourseIDs(array($course_id));
        if (isset($course_units_by_course[$course_id])) {
            return $course_units_by_course[$course_id];
        } else {
            return array();
        }
    }

    public function fetchAllByCourseIDs(array $course_ids) {
        global $db;
        if ($course_ids) {
            $query = "SELECT *
                      FROM `course_units`
                      WHERE `course_id` IN (".$this->quoteIDs($course_ids).")
                      AND `deleted_date` IS NULL
                      ORDER BY `unit_order` ASC";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("course_id", $results);
        } else {
            return array();
        }
    }

    public function fetchAllByCourseIDsAndCperiodID(array $course_ids, $cperiod_id) {
        global $db;
        if ($course_ids) {
            if ($cperiod_id) {
                $cperiod_sql = "AND cu.`cperiod_id` = ".$db->qstr($cperiod_id);
            } else {
                $cperiod_sql = "AND cu.`cperiod_id` IS NULL";
            }
            $query = "SELECT *
                      FROM `course_units`
                      WHERE `course_id` IN (".$this->quoteIDs($course_ids).")
                      ".$cperiod_sql."
                      AND `deleted_date` IS NULL
                      ORDER BY `unit_order` ASC";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("course_id", $results);
        } else {
            return array();
        }
    }

    protected function fromArray(array $result) {
        return new Models_Course_Unit($result);
    }
}
