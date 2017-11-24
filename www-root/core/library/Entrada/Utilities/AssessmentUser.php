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
 * Utility class used to fetch assessment users
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Utilities_AssessmentUser extends Entrada_Utilities_Assessments_Base {
    protected $proxy_id, $firstname, $lastname, $email, $number, $cperiod_ids, $assessment_count, $assessment_url, $photo_url;

    /**
     * Sets the options for the class object. For example, $options = array('courses' => '123') sets $this->courses = 123.
     * @param array $options
     */

    public function __construct($options = array()) {
        // if $options property exists among class properties, set it
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Gets the proxy_id of the user
     * @return int
     */

    public function getProxyID () {
        return $this->proxy_id;
    }

    /**
     * Gets the first name of the user
     * @return string
     */

    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Gets the last name of the user
     * @return string
     */

    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Gets the email of the user
     * @return string
     */

    public function getEmail () {
        return $this->email;
    }

    /**
     * Gets the number of the user
     * @return int
     */

    public function getNumber () {
        return $this->number;
    }

    /**
     * Gets the cperiods for the user
     * @return int
     */

    public function getCPeriodIDs () {
        return $this->cperiod_ids;
    }

    /**
     * Gets the total count of assessments for the user
     * @return int
     */

    public function getAssessmentCount () {
        return $this->assessment_count;
    }

    /**
     * Gets the assessment url for the user
     * @return string
     */

    public function getAssessmentUrl () {
        return $this->assessment_url;
    }

    /**
     * Gets the url used for the user's photo
     * @return string
     */

    public function getPhotoUrl () {
        return $this->photo_url;
    }

    /**
     * Fetches a list of learners for academic advisors
     * @param int proxy_id The id of the current user
     * @param int organisation_id The current organisation of the user
     * @return array
     */

    public function getMyLearners($proxy_id = null, $organisation_id = null, $admin = false, $search_term = null) {
        $users = array();

        $course_groups = Models_Course_Group::fetchAllGroupsByTutorProxyIDOrganisationID($proxy_id, $organisation_id);
        if ($course_groups) {
            foreach ($course_groups as $course_group) {
                $course = Models_Course::fetchRowByID($course_group->getCourseID());
                $course_learners = Models_User::fetchAllByCGroupIDSearchTerm($course_group->getID());
                if ($course && $course_learners) {
                    foreach ($course_learners as $user) {
                        if (is_null($search_term) || strpos(strtolower($user->getFirstname() . " " . $user->getLastname()), strtolower($search_term)) !== false) {
                            $users[$user->getID()] = array(
                                "id" => $user->getID(),
                                "proxy_id" => $user->getID(),
                                "firstname" => $user->getFirstname(),
                                "lastname" => $user->getLastname(),
                                "email" => $user->getEmail(),
                                "number" => $user->getNumber(),
                                "cperiod_ids" => array()
                            );
                        }
                    }
                }
            }
        }

        if ($admin) {
            $courses = Models_Course::fetchAllByOrg($organisation_id);
        } else {
            $courses = Models_Course::getUserCourses($proxy_id, $organisation_id);
        }

        // Program administrators should be able to see all learners in the course by default without a course group/tutor relationship.
        if ($courses) {
            foreach ($courses as $course) {
                if (CourseOwnerAssertion::_checkCourseOwner($proxy_id, $course->getID()) || $admin) {
                    $audience = $course->getAudience();

                    foreach ($audience as $audience_member) {
                        if ($audience_member->getAudienceType() == "group_id") {
                            $group_members = $audience_member->getMembers();
                            if ($group_members) {
                                foreach ($group_members as $member) {
                                    $user = Models_User::fetchRowByID($member->getID());
                                    if ($user) {
                                        if (!array_key_exists($user->getID(), $users)) {
                                            if (is_null($search_term) || strpos(strtolower($user->getFirstname() . " " . $user->getLastname()), strtolower($search_term)) !== false) {
                                                $users[$user->getID()] = array(
                                                    "id" => $user->getID(),
                                                    "proxy_id" => $user->getID(),
                                                    "firstname" => $user->getFirstname(),
                                                    "lastname" => $user->getLastname(),
                                                    "email" => $user->getEmail(),
                                                    "number" => $user->getNumber(),
                                                    "cperiod_ids" => array($audience_member->getCperiodID())
                                                );
                                            }
                                        } else {
                                            $old_user = $users[$user->getID()];
                                            if (!isset($old_user["cperiod_ids"]) || !is_array($old_user["cperiod_ids"]) || !in_array($audience_member->getCperiodID(), $old_user["cperiod_ids"])) {
                                                $old_user["cperiod_ids"][] = $audience_member->getCperiodID();
                                            }
                                            if (is_null($search_term) || strpos(strtolower($user->getFirstname() . " " . $user->getLastname()), strtolower($search_term)) !== false) {
                                                $old_user["id"] = $user->getID();
                                                $users[$user->getID()] = $old_user;
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $user = Models_User::fetchRowByID($audience_member->getAudienceValue());
                            if ($user) {
                                if (!array_key_exists($user->getID(), $users)) {
                                    if (is_null($search_term) || strpos(strtolower($user->getFirstname() . " " . $user->getLastname()), strtolower($search_term)) !== false) {
                                        $users[$user->getID()] = array(
                                            "id" => $user->getID(),
                                            "proxy_id" => $user->getID(),
                                            "firstname" => $user->getFirstname(),
                                            "lastname" => $user->getLastname(),
                                            "email" => $user->getEmail(),
                                            "number" => $user->getNumber(),
                                            "cperiods" => array($audience_member->getCperiodID())
                                        );
                                    }
                                } else {
                                    $old_user = $users[$user->getID()];
                                    if (!is_array($old_user["cperiod_ids"]) || !in_array($audience_member->getCperiodID(), $old_user["cperiod_ids"])) {
                                        $old_user["cperiod_ids"][] = $audience_member->getCperiodID();
                                    }
                                    if (is_null($search_term) || strpos(strtolower($user->getFirstname() . " " . $user->getLastname()), strtolower($search_term)) !== false) {
                                        $old_user["id"] = $user->getID();
                                        $users[$user->getID()] = $old_user;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        usort($users, array("Entrada_Utilities_AssessmentUser", "compareUserFirst"));
        return $users;
    }

    /**
     * Fetches a list of faculty for academic advisors
     * @param int proxy_id The id of the current user
     * @param int organisation_id The current organisation of the user
     * @return array
     */

    public function getFaculty ($proxy_id = null, $organisation_id = null, $admin = false, $url = null, $search_term = null, $sort_by_last = false) {
        $users = array();

        if ($admin) {
            $courses = Models_Course::fetchAllByOrg($organisation_id);
        } else {
            $courses = Models_Course::getUserCourses($proxy_id, $organisation_id);
        }

        if ($courses) {
            foreach ($courses as $course) {
                if (CourseOwnerAssertion::_checkCourseOwner($proxy_id, $course->getID()) || $admin) {

                    $tmp_users = array();

                    // Get all directors associated with this user's courses and put them into the user array

                    $directors = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "director");
                    if ($directors) {
                        foreach ($directors as $director) {
                            $tmp_users[$director["proxy_id"]] = $director;
                        }
                    }

                    // Get all pcoordinators associated with this user's courses and put them into the user array

                    $pcoordinators = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "pcoordinator");
                    if ($pcoordinators) {
                        foreach ($pcoordinators as $pcoordinator) {
                            $tmp_users[$pcoordinator["proxy_id"]] = $pcoordinator;
                        }
                    }


                    // Get all faculty associated with this user's courses and put them into the user array

                    $faculty_members = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "faculty");
                    if ($faculty_members) {
                        foreach ($faculty_members as $faculty) {
                            $tmp_users[$faculty["proxy_id"]] = $faculty;
                        }
                    }

                    // Get all associated faculty associated with this user's courses and put them into the user array

                    $associated_faculty_members = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "associated_faculty");
                    if ($associated_faculty_members) {
                        foreach ($associated_faculty_members as $associated_faculty_member) {
                            $tmp_users[$associated_faculty_member["proxy_id"]] = $associated_faculty_member;
                        }
                    }

                    foreach ($tmp_users as $id => $course_user) {
                        $user = Models_User::fetchRowByID($id);
                        if ($user) {
                            if (is_null($search_term) || strpos(strtolower($user->getFirstname() . " " . $user->getLastname()), strtolower($search_term)) !== false) {
                                $users[$user->getID()] = new Entrada_Utilities_AssessmentUser(array(
                                    "proxy_id" => $user->getID(),
                                    "firstname" => $user->getFirstname(),
                                    "lastname" => $user->getLastname(),
                                    "email" => $user->getEmail(),
                                    "number" => $user->getNumber(),
                                    "assessment_count" => null,//$this->getUserAssessmentCount($user->getID(), $organisation_id, "faculty"),
                                    "assessment_url" => $url . "/assessments/faculty?proxy_id=" . $user->getID(),
                                    "photo_url" => $url . "/api/photo.api.php/" . $user->getID() . "/official"
                                ));
                            }
                        }
                    }
                }
            }
        }

        usort($users, array("Entrada_Utilities_AssessmentUser", ($sort_by_last ? "compareUserLast" : "compareUserFirst")));

        return $users;
    }

    public function getUserAssessmentCount($proxy_id = null, $organisation_id = null, $current_section = "assessments") {
        // Get a count of all user assessments
        $all_user_assessments = Entrada_Utilities_Assessments_AssessmentTask::getAssessmentProgressOnUser($proxy_id, $organisation_id, $current_section);
        $complete_user_assessments = $all_user_assessments["complete"];

        if ($complete_user_assessments) {
            $user_assessment_count = count($complete_user_assessments);
        } else {
            $user_assessment_count = 0;
        }

        return $user_assessment_count;
    }

    /**
     * Usort callback function to compare two users by first name
     * then by last name
     * @param $user1
     * @param $user2
     * @return int
     */
    private function compareUserFirst($user1, $user2) {
        if (is_array($user1) && is_array($user2)) {
            $user1_last_name = strtolower($user1["lastname"]);
            $user2_last_name = strtolower($user2["lastname"]);

            if ($user1_last_name == $user2_last_name) {
                $user1_first_name = strtolower($user1["firstname"]);
                $user2_first_name = strtolower($user2["firstname"]);

                if ($user1_first_name == $user2_first_name) {
                    return 0;
                } else {
                    return ($user1_last_name > $user2_last_name) ? 1 : -1;
                }

            } else {
                return ($user1_last_name > $user2_last_name) ? 1 : -1;
            }
        } elseif (is_object($user1) && is_object($user2)) {
            $user1_last_name = strtolower($user1->getLastname());
            $user2_last_name = strtolower($user2->getLastname());

            if ($user1_last_name == $user2_last_name) {
                $user1_first_name = strtolower($user1->getFirstname());
                $user2_first_name = strtolower($user2->getFirstname());

                if ($user1_first_name == $user2_first_name) {
                    return 0;
                } else {
                    return ($user1_last_name > $user2_last_name) ? 1 : -1;
                }

            } else {
                return ($user1_last_name > $user2_last_name) ? 1 : -1;
            }
        } else {
            return 0;
        }
    }

    /**
     * Usort callback function to compare two users by first name
     * then by last name
     * @param $user1
     * @param $user2
     * @return int
     */
    private function compareUserLast($user1, $user2) {
        if (is_array($user1) && is_array($user2)) {
            $user1_last_name = strtolower($user1["firstname"]);
            $user2_last_name = strtolower($user2["firstname"]);

            if ($user1_last_name == $user2_last_name) {
                $user1_first_name = strtolower($user1["lastname"]);
                $user2_first_name = strtolower($user2["lastname"]);

                if ($user1_first_name == $user2_first_name) {
                    return 0;
                } else {
                    return ($user1_last_name > $user2_last_name) ? 1 : -1;
                }

            } else {
                return ($user1_last_name > $user2_last_name) ? 1 : -1;
            }
        } elseif (is_object($user1) && is_object($user2)) {
            $user1_last_name = strtolower($user1->getFirstname());
            $user2_last_name = strtolower($user2->getFirstname());

            if ($user1_last_name == $user2_last_name) {
                $user1_first_name = strtolower($user1->getLastname());
                $user2_first_name = strtolower($user2->getLastname());

                if ($user1_first_name == $user2_first_name) {
                    return 0;
                } else {
                    return ($user1_last_name > $user2_last_name) ? 1 : -1;
                }

            } else {
                return ($user1_last_name > $user2_last_name) ? 1 : -1;
            }
        } else {
            return 0;
        }
    }

    public function cacheUserCardPhotos($users = array()) {
        global $ENTRADA_ACL;

        if (!empty($users)) {
            $default_cached = false;
            $cache = new Entrada_Utilities_Cache();

            foreach ($users as $user) {
                if (!$cache->loadCache($user["id"])) {
                    $user_object = Models_User::fetchRowByID($user["id"]);
                    if ($user_object && (@file_exists(STORAGE_USER_PHOTOS . "/" . $user["id"] . "-official")) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($user["id"], (int) $user_object->getPrivacyLevel(), "official"), "read"))) {
                        $cache->cacheImage(STORAGE_USER_PHOTOS . "/" . $user["id"] . "-official" , $user["id"]);
                    } else {
                        if (!$default_cached) {
                            $cache->cacheImage(ENTRADA_ABSOLUTE . "/images/headshot-male.gif" , "default_photo", "image/gif");
                            $default_cached = true;
                        }
                    }
                }
            }
        }
    }

    public function getAssessorFacultyList($proxy_id, $organisation_id, $search_term = null, $add_externals = true, $sort_by_last = false) {
        $users = array();
        $courses = Models_Course::getUserCourses($proxy_id, $organisation_id);

        if ($courses) {
            $faculty = array();
            $course_contact_model = new Models_Assessments_Distribution_CourseContact();

            foreach ($courses as $course) {
                $course_contact_list = $course_contact_model->fetchAllByCourseID($course->getID(), $add_externals ? null : "internal", $search_term);
                if ($course_contact_list) {
                    foreach ($course_contact_list as $course_contact) {
                        $faculty[] = array("assessor_value" => $course_contact["assessor_value"], "assessor_type" => $course_contact["assessor_type"]);
                    }
                }
            }

            foreach ($faculty as $faculty_member) {
                $user_record = $this->getUserByType($faculty_member["assessor_value"], $faculty_member["assessor_type"]);

                if ($user_record && !array_key_exists($user_record->getID(), $users)) {
                    $users[$user_record->getID()] = array(
                        "id" => $user_record->getID(),
                        "firstname" => $user_record->getFirstname(),
                        "lastname" => $user_record->getLastname(),
                        "email" => $user_record->getEmail(),
                        "type" => $faculty_member["assessor_type"] == "external" ? "external" : "internal",
                    );
                }
            }
        }

        usort($users, array("Entrada_Utilities_AssessmentUser", ($sort_by_last ? "compareUserLast" : "compareUserFirst")));

        return $users;
    }
}