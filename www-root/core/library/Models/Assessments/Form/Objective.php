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
 * A model for handling Assessment Form Objectives
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Objective extends Models_Base {
    protected $assessment_form_objective_id, $form_id, $objective_id, $organisation_id, $course_id, $deleted_date;

    protected static $table_name = "cbl_assessment_form_objectives";
    protected static $primary_key = "assessment_form_objective_id";
    protected static $default_sort_column = "assessment_form_objective_id";

    public function getID() {
        return $this->assessment_form_objective_id;
    }

    public function getFormID () {
        return $this->form_id;
    }

    public function getObjectiveID () {
        return $this->objective_id;
    }

    public function getOrganisationID () {
        return $this->organisation_id;
    }

    public function getCourseID () {
        return $this->course_id;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }
    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "assessment_form_objective_id", "method" => ">=", "value" => "1")));
    }

    public function fetchAllByObjectiveListCourseIDOrganisationID($objectives = array(), $course_id = null, $organisation_id = null, $proxy_id = null, $group_by_form = true, $group_by_type = false) {
        global $db;

        $params = array($course_id, $proxy_id, $course_id, $organisation_id);
        $objective_list = implode(",", $objectives);
        $GROUP_by = "";
        $COUNT_form_id = "";
        $WHERE_form_count = "";
        if ($group_by_form) {
            $GROUP_by = " GROUP BY a.`form_id`";
        }
        if ($group_by_type) {
            if ($group_by_form) {
                $GROUP_by = " GROUP BY a.`form_id`, b.`origin_type`, `grouping_orig_id`, b.`form_type_id`";
            } else {
                $GROUP_by = " GROUP BY b.`origin_type`, `grouping_orig_id`, b.`form_type_id`";
                $COUNT_form_id = ", IF(b.`originating_id` IS NOT NULL AND b.`origin_type` = 'blueprint',
                                        ( SELECT COUNT(a1.`form_id`) FROM `cbl_assessment_form_objectives` AS a1
                                          JOIN `cbl_assessments_lu_forms` AS b1 ON a1.`form_id` = b1.`form_id`
                                          WHERE a1.`objective_id` IN ({$objective_list})
                                          AND a1.`course_id` = ?
                                          AND a1.`organisation_id` = ?
                                          AND a1.`deleted_date` IS NULL
                                          AND b1.`deleted_date` IS NULL 
                                          AND b1.`originating_id` = b.`originating_id`
                                        ), 1) AS form_count";
                $params = array_merge(array($course_id, $organisation_id), $params);
            }
        }



        $query = "SELECT a.*, b.*, ft.`shortname` AS form_type_shortname, ft.`title` AS form_type_title $COUNT_form_id, IFNULL(b.`originating_id`, UUID()) AS grouping_orig_id,
                  (   
                      SELECT avg(ap.`progress_time`)
                      FROM cbl_distribution_assessments AS da
                      JOIN cbl_distribution_assessment_targets AS dat ON da.`dassessment_id` = dat.`dassessment_id`
                      JOIN `cbl_assessment_progress` AS ap ON ap.`dassessment_id` = da.`dassessment_id`
                      WHERE da.`form_id` = a.`form_id`
                      AND ap.progress_time IS NOT NULL AND ap.progress_time > 0
                  ) AS average_time,
                  IFNULL (
                    (
                        SELECT `count` 
                        FROM `cbl_assessments_form_statistics` AS s
                        WHERE s.`course_id` = ? AND s.`form_id` = a.`form_id` AND s.`proxy_id` = ?
                    ), 0
                  ) AS completed_count
                  FROM `cbl_assessment_form_objectives` AS a
                  JOIN `cbl_assessments_lu_forms` AS b ON a.`form_id` = b.`form_id`
                  JOIN `cbl_assessments_lu_form_types` AS ft ON ft.`form_type_id` = b.`form_type_id` WHERE a.`objective_id` IN ({$objective_list})
                  AND a.`course_id` = ?
                  AND a.`organisation_id` = ?
                  AND a.`deleted_date` IS NULL
                  AND b.`deleted_date` IS NULL
                  $GROUP_by
                  ORDER BY b.`title`
          ";
        $res = $db->GetAll($query, $params);
        return $res;
    }

    /**
     * Return objectives associated with a form
     *
     * @param $form_id
     * @param bool $include_deleted
     * @return array
     */
    public static function fetchAllByFormID($form_id, $include_deleted = false) {
        global $db;

        $AND_deleted_date = $include_deleted ? "" : "AND fo.`deleted_date` IS NULL";
        $query = "SELECT o.`objective_id`, o.`objective_code`, o.`objective_name`, fo.`course_id`
                  FROM `cbl_assessment_form_objectives` AS fo
                  JOIN `global_lu_objectives` AS o ON fo.`objective_id` = o.`objective_id`
                  WHERE fo.`form_id` = ?
                  $AND_deleted_date";

        return $db->GetAll($query, array($form_id));
    }

    /**
     * Delete the rows by the specified list of IDs.
     *
     * @param $form_id_array
     * @return bool
     */
    public static function deleteByFormIDs($form_id_array) {
        global $db;
        $clean_array = array_map(function ($v) {
            return clean_input($v, array("trim", "int"));
        }, $form_id_array);
        if (empty($clean_array)) {
            return false;
        }
        $form_id_str = implode(",", $clean_array);
        $query = "UPDATE `cbl_assessment_form_objectives` AS fo SET fo.`deleted_date` = ? WHERE fo.`form_id` IN ({$form_id_str})";
        return $db->Execute($query, array(time()));
    }

    public static function deleteByFormIDObjectiveIDCourseID($form_id, $objective_id, $course_id) {
        global $db;

        $query = "UPDATE `cbl_assessment_form_objectives`
                  SET deleted_date = ?
                  WHERE `form_id` = ?
                  AND `objective_id` = ?
                  AND `course_id` = ?";

        return $db->Execute($query, array(time(), $form_id, $objective_id, $course_id));
    }
}