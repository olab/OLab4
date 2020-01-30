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
 * A model for managing the retrieval of course IDs to be used by the Visualization API.
 *
 * @author Organisation: Queen's University
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_CBME_Course extends Models_Base {

    /**
     * Build a list of courses that the actor has permission to view, optionally operating on a secondary id (learner).
     *
     * @param $group
     * @param $role
     * @param $organisation_id
     * @param $primary_id
     * @param null $secondary_id
     * @param null $cperiods
     * @return array
     */
    public function getActorCourses($group, $role, $organisation_id, $primary_id, $secondary_id = null, $cperiods = null) {
        if ($group === "student") {
            $courses = $this->getSelectedCoursePreference("learner", $organisation_id, $primary_id, null, $cperiods);
        } else {
            if ($group === "faculty") {
                switch ($role) {
                    case "director":
                        $courses = $this->getSelectedCoursePreference(($secondary_id ? "staff_on_learner" : "staff"), $organisation_id, $primary_id, $secondary_id);
                        break;
                    default:
                        $courses = $this->getSelectedCoursePreference(($secondary_id ? "faculty_on_learner" : "faculty"), $organisation_id, $primary_id, $secondary_id);
                        break;
                }
            } else {
                $courses = $this->getSelectedCoursePreference(($secondary_id ? "staff_on_learner" : "staff"), $organisation_id, $primary_id, $secondary_id);
            }
        }
        return $courses;
    }

    /**
     * Fetch a list of actor courses, optionally cross referencing against a secondary learner ID based on the specified
     * mode. This will return the actor's course preference if it happens to be in the list. Optionally limit to an
     * array of curriculum period IDs.
     *
     * @param $mode
     * @param $organisation_id
     * @param $primary_id
     * @param null $secondary_id
     * @param null $cperiod_ids
     * @return array
     */
    private function getSelectedCoursePreference($mode, $organisation_id, $primary_id, $secondary_id = null, $cperiod_ids = null) {
        global $PREFERENCES, $ENTRADA_ACL, $ENTRADA_USER;
        $courses = array();
        $admin = ($ENTRADA_USER->getActiveGroup() == "medtech" && $ENTRADA_USER->getActiveRole() == "admin") || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

        switch($mode) {
            /**
             * Learner operating on themselves
             */
            case "learner":
                $courses = $this->fetchLearnerCourses($primary_id, $organisation_id, true, $cperiod_ids);
                break;
            /**
             * Staff operating on themselves
             */
            case "staff":
                $courses = Models_Course::getUserCoursesList($primary_id, $organisation_id, $admin);
            break;
            /**
             * Faculty operating on themselves
             */
            case "faculty":
                $advisor_courses = Models_Course_Group::fetchAllGroupsByTutorProxyIDOrganisationID($primary_id, $organisation_id);
                if ($advisor_courses) {
                    foreach ($advisor_courses as $advisor_course) {
                        $course = Models_Course::fetchRowByID($advisor_course->getCourseID());
                        if ($course) {
                            $courses[$course->getID()] = $course->toArray();
                        }
                    }
                }
                break;
            /**
             * Staff operating on a learner
             * Cross reference staff and learner courses.
             */
            case "staff_on_learner":
                $learner_courses = $this->fetchLearnerCourses($secondary_id, $organisation_id, true,  $cperiod_ids);
                $staff_courses = Models_Course::getUserCoursesList($primary_id, $organisation_id, $admin);

                foreach ($learner_courses as $learner_course) {
                    foreach ($staff_courses as $staff_course) {
                        if ($staff_course["course_id"] == $learner_course["course_id"]) {
                            array_push($courses, $staff_course);
                        }
                    }
                }
            break;
            /**
             * Academic advisor operating on a learner.
             * Cross reference faculty course groups and learner courses.
             */
            case "faculty_on_learner":
                $learner_courses = $this->fetchLearnerCourses($secondary_id, $organisation_id, true,  $cperiod_ids);
                $advisor_courses = Models_Course_Group::facultyMemberIsTutor($primary_id, $secondary_id, $organisation_id);

                foreach ($learner_courses as $learner_course) {
                    foreach ($advisor_courses as $advisor_course) {
                        $advisor_course = $advisor_course->toArray();
                        if ($advisor_course["course_id"] == $learner_course["course_id"]) {
                            array_push($courses, $learner_course);
                        }
                    }
                }
            break;
        }

        /**
         * Add competency committee courses for all staff and faculty modes when operating on a secondary (learner) ID.
         **/
        if ($secondary_id && $ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($secondary_id), "read")) {
            $cc_courses = $this->getCCMemberCoursesOnLearner($primary_id, $secondary_id, $organisation_id, $cperiod_ids);
            if ($cc_courses) {
                $courses = array_merge($courses, $cc_courses);
            }
        }

        /**
         * Add courses for course_group_contacts
         **/
        if ($secondary_id && $ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($secondary_id), "read")) {
            $aa_courses = $this->getCourseGroupCoursesOnLearner($primary_id, $secondary_id, $organisation_id, $cperiod_ids);
            if ($aa_courses) {
                $courses = array_merge($courses, $aa_courses);
            }
        }

        /**
         * Indexes courses by course ID
         */
        $all_courses = array();
        foreach ($courses as $course) {
            $all_courses[$course["course_id"]] = $course;
        }

        /**
         * Use course preference if it is set, ensuring that preference is a possibility given the current course list. Otherwise use the first course in the courses array.
         */
        if (isset($PREFERENCES["course_preference"]) && array_key_exists("course_id", $PREFERENCES["course_preference"]) && array_key_exists("course_name", $PREFERENCES["course_preference"])
        && array_key_exists($PREFERENCES["course_preference"]["course_id"], $all_courses)) {
            $course_id = $PREFERENCES["course_preference"]["course_id"];
            $course_name = $PREFERENCES["course_preference"]["course_name"];
        } else {
            $course = reset($all_courses);
            $course_id = $course["course_id"];
            $course_name = $course["course_name"];
        }

        return array("course_id" => $course_id, "course_name" => $course_name, "courses" => $all_courses);
    }

    /**
     * Fetch all learner courses, optionally ordering by course code and limiting to an array of cperiod ids.
     *
     * @param $proxy_id
     * @param $organisation_id
     * @param bool $order_by_course_code
     * @param $cperiod_ids
     * @return array
     */
    private function fetchLearnerCourses($proxy_id, $organisation_id, $order_by_course_code = true, $cperiod_ids) {
        global $db;
        $courses = array();
        if ($cperiod_ids) {
            $cperiod_clause = "AND b.`cperiod_id` IN (" . implode(", ", $cperiod_ids) . ")";
        } else {
            $cperiod_clause = "";
        }

        $query = "	SELECT DISTINCT(a.`course_id`), a.`course_name`, a.`course_code`, a.`course_active`, a.`organisation_id` FROM `courses` AS a
                    LEFT JOIN `course_audience` AS b
                    ON a.`course_id` = b.`course_id`
                    LEFT JOIN `groups` AS c
                    ON b.`audience_type` = 'group_id'
                    AND b.`audience_value` = c.`group_id`
                    LEFT JOIN `group_members` AS d
                    ON d.`group_id` = c.`group_id`
                    WHERE `organisation_id` = ?
                    AND (
						d.`proxy_id` = ?
						OR (
							b.`audience_type` = 'proxy_id' AND b.`audience_value` = ?
						)
					)
					AND a.`course_active`='1'
					$cperiod_clause
					GROUP BY a.`course_id`
					ORDER BY" . ($order_by_course_code ? " a.`course_code`," : "") . " a.`course_name` ASC";

        $results = $db->GetAll($query, array($organisation_id, $proxy_id, $proxy_id));
        if ($results) {
            $courses = $results;
        }

        return $courses;
    }

    /**
     * Fetch current cperiod_ids based on the actor's organisation.
     *
     * @return array
     */
    public function getCurrentCPeriodIDs($organisation_id) {
        $cperiod_model = new Models_Curriculum_Period();
        return $cperiod_model->fetchAllCurrentIDsByOrganisation($organisation_id);
    }

    /**
     * Competency committee member operating on a learner.
     * Cross reference committee member courses and learner courses.
     *
     * @param int $primary_id
     * @param int $secondary_id
     * @param int $organisation_id
     * @param array $cperiod_ids
     * @return array $courses
     */
    public function getCCMemberCoursesOnLearner($primary_id, $secondary_id, $organisation_id, $cperiod_ids = null) {

        $courses = array();
        $course_model = new Models_Course();

        $ccmember_courses = $course_model->fetchAllCoursesByProxyIDContactType($primary_id, "ccmember");
        $learner_courses = $this->fetchLearnerCourses($secondary_id, $organisation_id, true, $cperiod_ids);

        foreach ($learner_courses as $learner_course) {
            foreach ($ccmember_courses as $ccmember_course) {
                $ccmember_course = $ccmember_course->toArray();
                if ($ccmember_course["course_id"] == $learner_course["course_id"]) {
                    array_push($courses, $learner_course);
                }
            }
        }

        return $courses;
    }

    /**
     * Course group contact operating on a learner.
     * Cross reference course group contact courses and learner courses.
     *
     * @param int $primary_id
     * @param int $secondary_id
     * @return array $courses
     */
    public function getCourseGroupCoursesOnLearner($primary_id, $secondary_id) {
        $courses = array();
        $course_model = new Models_Course();

        $course_group_contact_courses = $course_model->fetchAllByCourseGroupContact($primary_id);
        if ($course_group_contact_courses) {
            foreach ($course_group_contact_courses as $course_group_contact_course) {
                $learner_course = $this->getCourseGroupAudienceByCgroupIDProxyID($course_group_contact_course["cgroup_id"], $secondary_id);
                if ($learner_course) {
                    array_push($courses, $learner_course);
                }
            }
        }
        return $courses;
    }

    /**
     * Get the course for a learner in the specified course group
     * @param int $course_group_id
     * @param int $proxy_id
     * @return array
     */
    private function getCourseGroupAudienceByCgroupIDProxyID($course_group_id = 0, $proxy_id = 0) {
        $courses_array = array();
        $courses = $this->fetchCourseGroupAudienceByCgroupIDProxyID($course_group_id, $proxy_id);
        if ($courses) {
            $courses_array = $courses;
        }
        return $courses_array;
    }

    /*
     * Fetch the course for a learner in the specified course group
     * Fetch a course based on a users course_group_audience records
     * @param $proxy_id
     */
    private function fetchCourseGroupAudienceByCgroupIDProxyID($course_group_id, $proxy_id) {
        global $db;
        $courses = array();
        $query = "  SELECT co.* FROM `courses` AS co
                    JOIN `course_groups` as cg
                    ON co.`course_id` = cg.`course_id`
                    JOIN `course_group_audience` as cga
                    ON cg.`cgroup_id` = cga.`cgroup_id`
                    WHERE cg.`cgroup_id` = ?
                    AND cga.`proxy_id` = ?
                    AND co.`course_active` = 1
                    AND cg.`active` = 1
                    AND cga.`active` = 1
                    GROUP BY co.`course_id`";
        $results = $db->GetRow($query, array($course_group_id, $proxy_id));
        if ($results) {
            $courses = $results;
        }
        return $courses;
    }
}