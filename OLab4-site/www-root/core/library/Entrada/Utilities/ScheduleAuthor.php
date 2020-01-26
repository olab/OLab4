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
 * A class used to determine whether a given proxy_id has access (is an author)
 * to a schedule draft.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_ScheduleAuthor extends Entrada_Base {

    /**
     * Determine if the current user can access a schedule draft.
     *
     * @param $proxy_id
     * @param $organisation_id
     * @param $schedule_course_id
     * @param $schedule_draft_id
     * @return bool
     */
    public static function isAuthor($proxy_id, $organisation_id, $schedule_course_id, $schedule_draft_id) {
        if (Models_Schedule_Draft_Author::isAuthor($schedule_draft_id, $proxy_id)) {
            // User is explicitly set as author
            return true;
        }
        $courses = array(); // List of course IDs that the user has access to (is coordinator/director of)
        if ($user_courses = Models_Course::getUserCourses($proxy_id, $organisation_id)) {
            if (is_array($user_courses)) {
                foreach ($user_courses as $course) {
                    $courses[$course->getID()] = $course->getID();
                }
            }
        }
        if (Models_Schedule_Draft_Author::isAuthor($schedule_draft_id, $schedule_course_id, "course_id")) {
            if (in_array($schedule_course_id, $courses)) {
                // User is a coordinator, and the authorship is configured to allow coordinators access
                return true;
            }
        }
        return false;
    }

}