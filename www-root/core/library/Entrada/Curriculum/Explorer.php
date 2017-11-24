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
 * A class to clean up the curriculum explorer queries and counting functions.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Entrada_Curriculum_Explorer {
    
    public static function getCourseSpecificObjectiveSets($course_id) {
        global $db, $ENTRADA_USER;
        
        $query = "SELECT a.`objective_id`, a.`audience_value` AS `course_id`, b.`objective_name`
                    FROM `objective_audience` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`audience_value` = ?
                    AND a.`audience_type` = 'COURSE'
                    AND a.`organisation_id` = ?
                    AND b.`objective_active` = ?";
        return $db->GetAll($query, array($course_id, $ENTRADA_USER->getActiveOrganisation(), "1"));
    }
    
    public static function getMappedCourses($objective_parent, $course_id) {
        global $db, $ENTRADA_USER;
        
        $query = "SELECT b.`course_id`, b.`course_name`, `course_code`
                    FROM `course_objectives` AS a
                    JOIN `courses` As b
                    ON a.`course_id` = b.`course_id`
                    JOIN `objective_organisation` AS c
                    ON a.`objective_id` = c.`objective_id`
                    WHERE a.`objective_id` = ".$db->qstr($objective_parent)."
                    AND b.`course_active` = '1'
                    AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation()).
                    ($course_id ? " AND a.`course_id` = " . $db->qstr($course_id) : "")."
                    GROUP BY b.`course_id`";
        return $db->GetAll($query);
    }
    
    public static function getMappedEvents($objective_parent, $start = NULL, $end = NULL, $course_id = NULL, $group_id = NULL) {
        global $db, $ENTRADA_USER;
        
        $query = "SELECT c.`event_id`, c.`event_title`, c.`event_start`, d.`objective_name`, e.`course_code`, e.`course_name`, d.`objective_description`
                    FROM `event_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    JOIN `events` AS c
                    ON a.`event_id` = c.`event_id`
                    JOIN `global_lu_objectives` AS d
                    ON a.`objective_id` = d.`objective_id`
                    JOIN `courses` AS e
                    ON c.`course_id` = e.`course_id`
                    LEFT JOIN `event_audience` AS f
                    ON c.`event_id` = f.`event_id` ".
                    ($group_id ? " AND f.`audience_type` = 'cohort' " : "")."
                    WHERE a.`objective_id` = ".$db->qstr($objective_parent)."
                    AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation()).
                    (!is_null($start) ? " AND (c.`event_start` >= ".$db->qstr($start)." AND c.`event_start` <= ".$db->qstr($end).")" : "").
                    (!is_null($course_id) ? " AND c.`course_id` = " . $db->qstr($course_id) : "").
                    (!is_null($group_id) ? " AND f.`audience_value` = " . $db->qstr($group_id) : "")."
                    GROUP BY c.`event_id`
                    ORDER BY c.`course_id`, c.`event_start` DESC";
        return $db->GetAll($query);
    }
    
    public static function getMappedAssessments($objective_parent, $start = NULL, $end = NULL, $course_id = NULL, $group_id = NULL) {
        global $db;
        
        $query = "SELECT a.`assessment_id`, a.`objective_id`, b.`name`, b.`description`, b.`cohort`, c.`course_code`, c.`course_name`, c.`course_id`, e.`event_id`, f.`event_start`
                    FROM `assessment_objectives` AS a
                    JOIN `assessments` AS b
                    ON a.`assessment_id` = b.`assessment_id`
                    JOIN `courses` AS c
                    ON b.`course_id` = c.`course_id`
                    JOIN `assessment_events` AS e
                    ON a.`assessment_id` = e.`assessment_id`
                    JOIN `events` AS f
                    ON e.`event_id` = f.`event_id`
                    WHERE a.`objective_id` = " . $db->qstr($objective_parent) . "
                    AND e.`active` = 1" .
                    (!is_null($start) ? " AND (f.`event_start` >= ".$db->qstr($start)." AND f.`event_start` <= ".$db->qstr($end).")" : "").
                    (!is_null($course_id) ? " AND b.`course_id` = " . $db->qstr($course_id) : "").
                    (!is_null($group_id) ? " AND b.`cohort` = " . $db->qstr($group_id) : "")."
                    GROUP BY b.`assessment_id`";
        return $db->GetAll($query);
    }
    
    public static function getChildObjectives($objective_parent) {
        global $db, $ENTRADA_USER;
        
        $query = "SELECT a.`objective_id`, a.`objective_name`, a.`objective_parent`
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`objective_parent` = " . $db->qstr($objective_parent) . "
                    AND a.`objective_active` = '1'
                    AND b.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    GROUP BY a.`objective_id`
                    ORDER BY a.`objective_id` ASC";
        return $db->GetAll($query);
    }
    
    public static function getObjectiveSets() {
        return Entrada_Curriculum::getObjectiveSets();
    }
    
    public static function getCohorts() {
        return Entrada_Curriculum::getCohorts();
    }
    
    public static function getCourses() {
        return Entrada_Curriculum::getCourses();
    }
    
    /*
     * Recursive function to fetch an objectives parents.
     *
     * @param int $objective_id
     * @param int $level
     * @return array
     */
    public static function fetch_objective_parents($objective_id, $level = 0) {
        global $db, $ENTRADA_USER;
        if ($level >= 99) {
            exit;
        }
        $query = "	SELECT a.`objective_parent`, a.`objective_id`, a.`objective_name`
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`objective_id` = ".$db->qstr($objective_id)."
                    AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
        $objective = $db->GetAssoc($query);
        if ($objective) {
            foreach ($objective as $parent_id => $objective_data)
            if ($parent_id != 0) {
                $objective_data["parent"] = self::fetch_objective_parents($parent_id, $level++);
            }
            return $objective_data;
        }
    }

    public static function count_objective_child_events($objective_id = 0, $start = NULL, $end = NULL, $course_id = NULL, $group_id = NULL, $level = 0) {
        global $db, $ENTRADA_USER;
        if ($level >= 99) {
            application_log("error", "Recursion depth out of bounds in [count_objective_child_events].");
            return false;
        }

        $objective_id = (int) $objective_id;

        /* Fetch Objective Mapped Events */
        $query = "	SELECT COUNT(DISTINCT a.`event_id`) AS `event_count`
                    FROM `event_objectives` AS a
                    JOIN `events` AS b
                    ON a.`event_id` = b.`event_id`
                    LEFT JOIN `event_audience` AS c
                    ON a.`event_id` = c.`event_id`".
                    ($group_id != NULL ? " AND c.`audience_type` = 'cohort' " : "")."
                    WHERE `objective_id` = ".$db->qstr($objective_id).
                    ($start != NULL ? " AND (IF (b.`event_id` IS NOT NULL, b.`event_start` BETWEEN ".$db->qstr($start)." AND ".$db->qstr($end).", '1' = '1'))" : "").
                    ($course_id != NULL ? " AND b.`course_id` = ".$db->qstr($course_id) : "").
                    ($group_id != NULL ? " AND c.`audience_value` = ".$db->qstr($group_id) : "");
        $output[$objective_id] = $db->GetOne($query);

        /* Fetch objective children */
        $query = "	SELECT a.`objective_id`
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`objective_parent` = ".$db->qstr($objective_id)."
                    AND a.`objective_active` = '1'
                    AND b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation();
        $children = $db->GetAll($query);
        if ($children) {
            foreach ($children as $child) {
                $child_count = self::count_objective_child_events($child["objective_id"], $start, $end, $course_id, $group_id, $level++);

                if (is_array($child_count)) {
                    $return = array_sum($child_count);
                } else {
                    $return = $event_count;
                }
                $output[$child["objective_id"]] = $return;
            }
        }

        return $output;

    }

    public static function count_objective_child_courses($objective_id = 0, $level = 0) {
        global $db, $ENTRADA_USER;
        if ($level >= 99) {
            application_log("error", "Recursion depth out of bounds in [count_objective_child_courses].");
            return false;
        }

        $objective_id = (int) $objective_id;

        /* Fetch Objective Mapped Courses */
        $query = "	SELECT COUNT(DISTINCT a.`course_id`) AS `course_count`
                    FROM `course_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    JOIN `courses` AS c
                    ON a.`course_id` = c.`course_id`
                    WHERE a.`objective_id` = ".$db->qstr($objective_id)."
                    AND a.`active` = '1'
                    AND c.`course_active` = '1'
                    AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
        $output[$objective_id] = $db->GetOne($query);

        /* Fetch objective children */
        $query = "	SELECT a.`objective_id`
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`objective_parent` = ".$db->qstr($objective_id)."
                    AND a.`objective_active` = '1'
                    AND b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation();
        $children = $db->GetAll($query);
        if ($children) {
            foreach ($children as $child) {
                $child_count = self::count_objective_child_courses($child["objective_id"], $level++);
                if (is_array($child_count)) {
                        $return = array_sum($child_count);
                } else {
                    $return = $course_count;
                }
                $output[$child["objective_id"]] = $return;
            }
        }

        return $output;

    }

    public static function count_objective_child_assessments($objective_id = 0, $start = NULL, $end = NULL, $course_id = NULL, $group_id = NULL, $level = 0) {
        global $db, $ENTRADA_USER;
        if ($level >= 99) {
            application_log("error", "Recursion depth out of bounds in [count_objective_child_events].");
            return false;
        }

        $objective_id = (int) $objective_id;

        /* Fetch Objective Mapped Events */
        $query = "SELECT COUNT(DISTINCT a.`assessment_id`) AS `assessment_count`
                    FROM `assessment_objectives` AS a
                    JOIN `assessments` AS b
                    ON a.`assessment_id` = b.`assessment_id`
                    JOIN `courses` AS c
                    ON b.`course_id` = c.`course_id`
                    JOIN `assessment_events` AS e
                    ON a.`assessment_id` = e.`assessment_id`
                    JOIN `events` AS f
                    ON e.`event_id` = f.`event_id`
                    WHERE a.`objective_id` = " . $db->qstr($objective_id) . "
                    AND e.`active` = 1" .
                    (!is_null($start) && !is_null($end) ? " AND (f.`event_start` >= ".$db->qstr($start)." AND f.`event_start` <= ".$db->qstr($end).")" : "").
                    (!is_null($course_id) ? " AND b.`course_id` = " . $db->qstr($course_id) : "").
                    (!is_null($group_id) ? " AND b.`cohort` = " . $db->qstr($group_id) : "");
        $output[$objective_id] = $db->GetOne($query);

        /* Fetch objective children */
        $query = "	SELECT a.`objective_id`
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`objective_parent` = ".$db->qstr($objective_id)."
                    AND a.`objective_active` = '1'
                    AND b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation();
        $children = $db->GetAll($query);
        if ($children) {
            foreach ($children as $child) {
                $child_count = self::count_objective_child_assessments($child["objective_id"], NULL, NULL, NULL, $group_id, $level++);

                if (is_array($child_count)) {
                    $return = array_sum($child_count);
                } else {
                    $return = $assessment_count;
                }
                $output[$child["objective_id"]] = $return;
            }
        }

        return $output;

    }
    
}