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
 * A model for handeling courses
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */

class Models_Course extends Models_Base {
    protected $course_id,
            $organisation_id,
            $curriculum_type_id,
            $parent_id = 0,
            $pcoord_id = 0,
            $evalrep_id = 0,
            $studrep_id = 0,
            $course_name,
            $course_code,
            $course_credit,
            $course_description,
            $course_mandatory,
            $course_objectives,
            $unit_collaborator = "",
            $unit_communicator = "",
            $unit_health_advocate = "",
            $unit_manager = "",
            $unit_scholar = "",
            $unit_professional = "",
            $unit_medical_expert = "",
            $unit_summative_assessment = "",
            $unit_formative_assessment = "",
            $unit_grading = "",
            $resources_required = "",
            $resources_optional = "",
            $course_url,
            $course_redirect,
            $course_message = "",
            $permission,
            $sync_ldap,
            $sync_ldap_courses,
            $sync_groups,
            $notifications,
            $course_twitter_handle,
            $course_twitter_hashtags,
            $course_color,
            $course_active = 1;
    
    protected static $table_name = "courses";
    protected static $default_sort_column = "course_id";
    protected static $primary_key = "course_id";
    
    public function getID () {
        return $this->course_id;
    }
    
    public function getOrganisationID () {
        return $this->organisation_id;
    }
    
    public function getCurriculumTypeID () {
        return $this->curriculum_type_id;
    }
    
    public function getParentID () {
        return $this->parent_id;
    }
    
    public function getPcoordID () {
        return $this->pcoord_id;
    }
    
    public function getEvalrepID () {
        return $this->evalrep_id;
    }
    
    public function getStudrepID () {
        return $this->studrep_id;
    }
    
    public function getCourseName () {
        return $this->course_name;
    }
    
    public function getCourseCode () {
        return $this->course_code;
    }

    public function getCourseCredit () {
        return $this->course_credit;
    }  

    public function getCourseDescription () {
        return $this->course_description;
    }

    public function getCourseMandatory () {
        return $this->course_mandatory;
    }

    public function getCourseObjectives () {
        return $this->course_objectives;
    }
    
    public function getUnitCollaborator () {
        return $this->unit_collaborator;
    }
    
    public function getUnitCommunicator () {
        return $this->unit_communicator;
    }
    
    public function getUnitHealthAdvocate () {
        return $this->unit_health_advocate;
    }
    
    public function getUnitManager () {
        return $this->unit_manager;
    }
    
    public function getUnitScholar () {
        return $this->unit_scholar;
    }
    
    public function getUnitProfessional () {
        return $this->unit_professional;
    }
    
    public function getUnitMedicalExpert () {
        return $this->unit_medical_expert;
    }
    
    public function getUnitSummativeAssessment () {
        return $this->unit_summative_assessment;
    }
    
    public function getUnitFormativeAssessment () {
        return $this->unit_formative_assessment;
    }
    
    public function getUnitGrading () {
        return $this->unit_grading;
    }
    
    public function getResourcesRequired () {
        return $this->resources_required;
    }
    
    public function getResourcesOptional () {
        return $this->resources_optional;
    }
    
    public function getCourseUrl () {
        return $this->course_url;
    }

    public function getCourseRedirect () {
        return $this->course_redirect;
    }
    
    public function getCourseMessage () {
        return $this->course_message;
    }
    
    public function getPermission () {
        return $this->permission;
    }
    
    public function getSyncLdap () {
        return $this->sync_ldap;
    }
    
    public function getSyncLdapCourses () {
        return $this->sync_ldap_courses;
    }

    public function getSyncGroups () {
        return $this->sync_groups;
    }

    public function getNotifications () {
        return $this->notifications;
    }
   
    public function getActive () {
        return $this->course_active;
    }

    public function getTwitterHandle () {
        return $this->course_twitter_handle;
    }

    public function getTwitterHashTags () {
        return $this->course_twitter_hashtags;
    }

    public function getColor() {
        return $this->course_color;
    }

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    /* @return bool|Models_Community */
    public function getCommunity() {
        if ($this->community != null) {
            return $this->community;
        } else {
            $course_community = Models_Community_Course::fetchRowByCourseID($this->course_id);
            if (isset($course_community) && is_object($course_community)) {
                return $this->community = Models_Community::fetchRowByID($course_community->getCourseID());
            } else {
                return null;
            }
        }
    }

    /* @return bool|Models_Course */
    public static function fetchRowByID($course_id = NULL, $course_active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "course_active", "value" => $course_active, "method" => "=")
        ));
    }
    
    /* @return bool|Models_Course */
    public static function get($course_id) {
        $self = new self();
        return $self->fetchRow(array("course_id" => $course_id, "course_active" => 1));
    }
    
    public static function fetchAllByOrg($org_id) {
        $self = new self();
        return $self->fetchAll(array("organisation_id" => $org_id, "course_active" => "1"));
    }

    /* @return ArrayObject|Models_Course_Audience */
    public function getAudience ($cperiod_id = null) {
        $audience = new Models_Course_Audience();
        return $audience->fetchAllByCourseIDCperiodID($this->getID(), $cperiod_id);
    }
    
    public function getMembers ($cperiod_id = null, $search_term = false) {
        $course_audience = $this->getAudience($cperiod_id);
        $a = array();
        if ($course_audience) {
            foreach ($course_audience as $audience) {
                if ($audience->getAudienceType() == "group_id") {
                    $audience_members = $audience->getMembers($search_term);
                    if ($audience_members) {
                        $a["groups"][$audience->getGroupName()] = $audience_members;
                    } 
                } else if ($audience->getAudienceType() == "proxy_id") {
                    $audience_member = $audience->getMember($search_term);
                    if ($audience_member) {
                        $a["individuals"][] = $audience_member;
                    }
                }
            }
        }
        ksort($a);
        return $a;
    }

    /**
     * Get array of student IDs
     * @param  int  $cperiod_id      Curriculum Period ID
     * @param  boolean $search_term  Search term
     * @return array                 Student IDs
     */
    public function getStudentIDs($cperiod_id = null, $search_term = false) {
        $course_members = $this->getMembers($cperiod_id, $search_term);
        $student_ids = array();

        // Add each student ID to array
        // 1st- process groups
        if (isset($course_members["groups"])) {
            foreach ($course_members["groups"] as $students) {
                foreach($students as $student) {
                    $student_ids[] = $student->getID();
                }
            }
        }

        // Process individuals
        if (isset($course_members["individuals"])) {
            foreach($course_members["individuals"] as $student) {
                $student_ids[] = $student->getID();
            }
        }
        
        return $student_ids;
    }

    public function getAllMembers ($cperiod_id = null, $search_term = false) {
        $course_audience = $this->getAudience($cperiod_id);
        $a = array();
        if ($course_audience) {
            foreach ($course_audience as $audience) {
                if ($audience->getAudienceType() == "group_id") {
                    $audience_members = $audience->getMembers($search_term);
                    if ($audience_members) {
                        foreach ($audience_members as $audience_member) {
                            $a[$audience_member->getID()] = $audience_member;
                        }
                    }
                } else if ($audience->getAudienceType() == "proxy_id") {
                    $audience_member = $audience->getMember($search_term);
                    if ($audience_member) {
                        $a[$audience_member->getID()] = $audience_member;
                    }
                }
            }
        }
        return $a;
    }
    
    public function update() {
		global $db;
		if ($db->AutoExecute("`courses`", $this->toArray(), "UPDATE", "`course_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}

    /**
     * Returns the course title including the curriculum type.
     * @return string  Ex. "Term 3: Professional Foundations: Critical Enquiry"
     */
    public function getFullCourseTitle() {
        global $db;

        $query = "SELECT * FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    LEFT JOIN `".DATABASE_NAME."`.`curriculum_lu_types` b
                    ON a.curriculum_type_id = b.curriculum_type_id
                    WHERE a.`".static::$primary_key."` = ?";

        $result = $db->getRow($query, array($this->getID()));

        if ($result) {
            return (!empty($result['curriculum_type_name']) ? $result['curriculum_type_name'] . ': ' : '') . $result['course_name'];
        }
        
        return false;
    }

    public function getTeachersByDates($event_start = null, $event_finish = null) {
        global $db;
        $teachers = false;
        
        $query = "  SELECT a.`event_id`, b.*, c.`firstname`, c.`lastname`, c.`email` FROM `events` AS a 
                    JOIN `event_contacts` AS b
                    ON a.`event_id` = b.`event_id`
                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
                    ON b.`proxy_id` = c.`id`
                    WHERE a.`course_id` = ? 
                    AND a.`event_start` >= ?
                    AND a.`event_finish` <= ?
                    AND b.`contact_role` = ?
                    GROUP BY b.`proxy_id`";
        
        $results = $db->GetAll($query, array($this->course_id, $event_start, $event_finish, "teacher"));
        if ($results) {
            foreach ($results as $result) {
                $teacher = new User();
                $teachers[] = $teacher->fromArray($result, $teacher);
            }
        }
        return $teachers;
    }

    public static function checkCourseOwner($course_id = null, $proxy_id = null) {
        global $db;

        $query	=  "SELECT a.`pcoord_id` AS `coordinator`, b.`proxy_id` AS `director_id`, d.`proxy_id` AS `admin_id`, e.`proxy_id` AS `pcoordinator`
					FROM `".DATABASE_NAME."`.`courses` AS a
					LEFT JOIN `".DATABASE_NAME."`.`course_contacts` AS b
					ON b.`course_id` = a.`course_id`
					AND b.`contact_type` = 'director'
					LEFT JOIN `".DATABASE_NAME."`.`community_courses` AS c
					ON c.`course_id` = a.`course_id`
					LEFT JOIN `".DATABASE_NAME."`.`community_members` AS d
					ON d.`community_id` = c.`community_id`
					AND d.`member_active` = '1'
					AND d.`member_acl` = '1'
					LEFT JOIN `".DATABASE_NAME."`.`course_contacts` AS e
					ON e.`course_id` = a.`course_id`
					AND e.`contact_type` = 'pcoordinator'
					WHERE a.`course_id` = ".$db->qstr($course_id)."
					AND (a.`pcoord_id` = ".$db->qstr($proxy_id)."
						OR b.`proxy_id` = ".$db->qstr($proxy_id)."
						OR d.`proxy_id` = ".$db->qstr($proxy_id)."
						OR e.`proxy_id` = ".$db->qstr($proxy_id)."
					)
					AND a.`course_active` = '1'
					LIMIT 0, 1";
        $result = $db->GetRow($query);
        if ($result) {
            foreach (array("director_id", "coordinator", "admin_id", "pcoordinator") as $owner) {
                if ($result[$owner] == $proxy_id) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getUserCourses ($proxy_id, $organisation_id, $search_value = null, $active = 1) {
        global $db, $ENTRADA_USER, $ENTRADA_ACL;
        $courses = false;

        $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
        if ($ENTRADA_USER->getActiveRole() == "admin" || $admin) {
            $query = "      SELECT * FROM `courses`
                            WHERE `organisation_id` = ?
                            AND `course_active` = ? ";
            if($search_value != null) {
                $query .= " AND `course_name` LIKE (" . $db->qstr("%" . $search_value . "%") . ")";
            }

            $query .="      GROUP BY `course_id`
                            ORDER BY `course_name`";

            $results = $db->GetAll($query, array($organisation_id, $active));
        } else {
            $query = "      SELECT a.* FROM `courses` AS a
                            LEFT JOIN `course_contacts` AS b
                            ON a.`course_id` = b.`course_id`
                            WHERE
                            (
                                (
                                    (
                                        b.`contact_type` = 'director'
                                        OR b.`contact_type` = 'ccoordinator'
                                        OR b.`contact_type` = 'pcoordinator'
                                    )
                                    AND b.`proxy_id` = ?
                                )
                                OR
                                (
                                    a.`pcoord_id` = ?
                                )
                            )
                            AND a.`organisation_id` = ?
                            AND a.`course_active` = ? ";
            if($search_value != null) {
                $query .= " AND a.`course_name` LIKE (". $db->qstr("%". $search_value ."%") .")";
            }
            $query .= "     GROUP BY a.`course_id`
                            ORDER BY a.`course_name`";
            $results = $db->GetAll($query, array($proxy_id, $proxy_id, $organisation_id, $active));
        }
        if ($results) {
            foreach ($results as $result) {
               $courses [] = new self($result);
            }
        }
        
        return $courses;
    }

    public static function getActiveUserCoursesIDList() {
        global $db, $ENTRADA_USER, $ENTRADA_ACL;

        $course_list = array();
        $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
        $AND_PROXY_ID = $JOIN_COURSE_CONTACTS = "";

        if (!$admin) {
            $JOIN_COURSE_CONTACTS = "   JOIN `course_contacts` AS b
                                        ON a.`course_id` = b.`course_id` ";
            $proxy_id = $ENTRADA_USER->getActiveId();
            $AND_PROXY_ID = " AND b.`proxy_id` = $proxy_id ";
        }

        $query = "  
                SELECT a.* FROM `courses` AS a
                $JOIN_COURSE_CONTACTS
                WHERE a.`organisation_id` = ?
                AND a.`course_active` = 1 
                $AND_PROXY_ID ";

        $results = $db->GetAll($query, array($ENTRADA_USER->getActiveOrganisation()));

        if ($results) {
            foreach ($results as $result) {
                $course_list[] = $result["course_id"];
            }
        }

        return $course_list;
    }

    public static function fetchAllByIDs($course_ids = array(), $active = 1) {
        global $db;

        $courses = array();

        $active = (int) $active;

        if (is_array($course_ids)) {
            foreach ($course_ids as &$id) {
                $id = (int) $id;
            }
        } else {
            $course_ids = array((int) $course_ids);
        }

        if ($course_ids) {
            $query = "SELECT * FROM `courses`
                      WHERE `course_id` IN (" . implode(", ", $course_ids) . ")
                      AND `course_active` = ?
                      ORDER BY `course_code`, `course_name`";
            $results = $db->GetAll($query, array($active));
            if ($results) {
                foreach ($results as $result) {
                    $courses[] = new self($result);
                }
            }
        }

        return $courses;
    }

    public function getRowByID($course_id, $active = 1) {
        global $db;

        $query = "SELECT *
                    FROM `courses` 
                    WHERE `course_id` = ?
                    AND `course_active` = ?";
        
        $results = $db->GetAll($query, array($course_id, $active));
        if ($results) {
            return $results;
        }

        return false;
    }


    public static function toSearchable ($courses = array(), $required = true) {
        $data = array();
        if ($courses) {
            foreach ($courses as $course) {
                $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName());
            }
        }

        if (!$required) {
            array_unshift($data, array("target_id" => 0, "target_label" => "No Course Affiliation"));
        }
        
        return json_encode($data);
    }

    public static function fetchCourseContactUsersByGroup ($course_id, $search_value = "", $group = null, $limit = null, $offset = null) {
        global $db;

        $query = "	SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    JOIN `courses` AS c
                    ON c.`course_id` = ?
                    LEFT JOIN `course_contacts` AS d
                    ON c.`course_id` = d.`course_id`
                    AND a.`id` = d.`proxy_id`
                    WHERE b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND b.`group` = ?
                    AND (
                        a.`id` = c.`pcoord_id`
                        OR a.`id` = c.`evalrep_id`
                        OR a.`id` = d.`proxy_id`
                    )
                    AND CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE ?
                    GROUP BY a.`id`
                    UNION
                    SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    JOIN `events` AS e
                    ON e.`course_id` = ?
                    JOIN `event_contacts` AS f
                    ON f.`event_id` = e.`event_id`
                    AND a.`id` = f.`proxy_id`
                    WHERE b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND b.`group` = ?
                    AND a.`id` = f.`proxy_id`
                    AND CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE ?
                    GROUP BY a.`id`
                    UNION
                    SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    JOIN `community_members` AS h
                    ON h.`proxy_id` = a.`id`
                    JOIN `community_courses` AS g
                    ON h.`community_id` = g.`community_id`
                    AND g.`course_id` = ?
                    WHERE b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND b.`group` = ?
                    AND CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE ?
                    GROUP BY a.`id`
                    ORDER BY `firstname` ASC, `lastname` ASC";

        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }

        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }
        $results = $db->GetAll($query, array($course_id, time(), time(), $group, "%".$search_value."%", $course_id, time(), time(), $group, "%".$search_value."%", $course_id, time(), time(), $group, "%".$search_value."%"));

        return $results;
    }

    public static function fetchAllContactsByCourseIDContactTypeSearchTerm ($course_id, $contact_type, $search_term = null) {
        global $db;
        $results = array();
        if ($course_id) {
            $constraints = array($course_id);

            $query = "SELECT * FROM `course_contacts` AS a";
            if ($search_term) {
                $query .= " JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                            ON a.`proxy_id` = b.`id`";
            }
            $query .= " WHERE a.`course_id` = ?
                        AND a.`contact_type` = '$contact_type'";
            if ($search_term) {
                $query .= " AND (CONCAT(b.`firstname`, ' ', b.`lastname`) LIKE ? OR b.`email` LIKE ?)";
                $constraints[] = "%".$search_term."%";
                $constraints[] = "%".$search_term."%";
            }

            $results = $db->GetAll($query, $constraints);
        }
        return $results;
    }

    public static function checkFacultyAssociationOwnership ($proxy_id, $organisation_id, $faculty_id, $assessor_type) {
        $courses = Models_Course::getUserCourses($proxy_id, $organisation_id);
        if ($courses) {
            foreach ($courses as $course) {
                if (CourseOwnerAssertion::_checkCourseOwner($proxy_id, $course->getID())) {
                    $associated_faculty = self::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "associated_faculty");
                    if ($associated_faculty) {
                        foreach ($associated_faculty as $faculty) {
                            if ($faculty["proxy_id"] == $faculty_id) {
                                return true;
                            }
                        }
                    }
                    $assessment_user = new Entrada_Utilities_AssessmentUser();
                    $faculty = $assessment_user->getAssessorFacultyList($proxy_id, $organisation_id);
                    if ($faculty) {
                        foreach ($faculty as $faculty) {
                            if ($faculty["id"] == $faculty_id && $faculty["type"] == $assessor_type) {
                                return true;
                            }
                        }
                    }
                    $course_directors = self::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "director");
                    if ($course_directors) {
                        foreach ($course_directors as $course_director) {
                            if ($course_director["proxy_id"] == $faculty_id) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function insertTrackCourseRelationship($curriculum_track_id, $course_id, $mandatory) {
        global $db;

        $params = array("curriculum_track_id" => $curriculum_track_id, "course_id" => $course_id, "track_mandatory" => $mandatory);
        if ($db->AutoExecute("course_tracks", $params, "INSERT")) {
            return true;
        }
        return false;
    }

    public static function deleteTrackCourseRelationshipByCourseId($course_id) {
        global $db;

        $query = "DELETE FROM `course_tracks` WHERE `course_id` = ?";
        if ($db->Execute($query, array($course_id))) {
            return true;
        }
        return false;
    }

    public static function getCourseTracks($course_id) {
        global $db;

        $query = "SELECT `c`.*, `b`.*
                    FROM `courses` AS `a`
                    JOIN `course_tracks` AS `b`
                    ON `b`.`course_id` = `a`.`course_id`
                    JOIN `curriculum_lu_tracks` AS `c`
                    ON `c`.`curriculum_track_id` = `b`.`curriculum_track_id`
                    WHERE `c`.`deleted_date` IS NULL AND `a`.`course_id` = ?";
        $results = $db->getAll($query, array($course_id));

        if ($results) {
            return $results;
        }
        return false;
    }

    public function getTotalReadOnlyCourses($organisation_id, $search_term = ""){
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND ( `courses`.`course_name` LIKE (". $db->qstr($search_term) . ")
                            OR `courses`.`course_code` LIKE (". $db->qstr($search_term) . ")
                            ) ";
        }

        $query = "	SELECT COUNT(*) AS `total_rows` FROM `courses` WHERE `courses`.`course_active` = '1' " . $search_sql . " AND `organisation_id` = ?";
        $results = $db->GetRow($query, array($organisation_id));
        if ($results) {
            return $results;
        }
        return false;
    }

    public function getTotalFullAccessCourses($proxy_id, $organisation_id, $search_term = ""){
        global $db;

        $search_sql = "";
        if (!empty($search_term)) {
            $search_sql = " AND ( `a`.`course_name` LIKE (". $db->qstr($search_term) . ")
                            OR `a`.`course_code` LIKE (". $db->qstr($search_term) . ")
                            ) ";
        }

        $query = "SELECT COUNT(*) AS `total_rows`
                    FROM `courses` AS a
                    LEFT JOIN `course_contacts` AS b
                    ON b.`course_id` = a.`course_id`
                    AND b.`proxy_id` = ?
                    AND b.`contact_type` = 'director'
                    LEFT JOIN `community_courses` AS c
                    ON c.`course_id` = a.`course_id`
                    LEFT JOIN `community_members` AS d
                    ON d.`community_id` = c.`community_id`
                    AND d.`proxy_id` = ?
                    WHERE
                    (
                        a.`pcoord_id` = ?
                        OR b.`proxy_id` = ?
                        OR d.`member_acl` = '1'
                    )
                    " . $search_sql . "
                     AND `a`.`organisation_id` = ?
                    AND a.`course_active` = '1' ";

        return $db->GetRow($query, array($proxy_id, $proxy_id, $proxy_id, $proxy_id, $organisation_id));
    }

    public function getReadOnlyCourses($organisation_id, $search_term = "", $offset = null, $limit = null, $sort_column = null, $sort_direction = null){
        global $db;

        $sort_columns_array = array(
            "type" => "`curriculum_lu_types`.`curriculum_type_order`",
            "name" => "`courses`.`course_name`",
            "code" => "`courses`.`course_code`",
        );

        $order_sql = " ORDER BY ".$sort_columns_array[$sort_column]. " ".$sort_direction." " ;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND ( `courses`.`course_name` LIKE (". $db->qstr($search_term) . ")
                            OR `courses`.`course_code` LIKE (". $db->qstr($search_term) . ")
                            ) ";
        }

        $query = "	SELECT `courses`.`course_id`, `courses`.`organisation_id`, `courses`.`course_name`, `courses`.`course_code`, `courses`.`course_url`, `courses`.`notifications`, `curriculum_lu_types`.`curriculum_type_name`, CONCAT_WS(', ', `".AUTH_DATABASE."`.`user_data`.`lastname`, `".AUTH_DATABASE."`.`user_data`.`firstname`) AS `fullname`
					FROM `courses`
					LEFT JOIN `curriculum_lu_types`
					ON `curriculum_lu_types`.`curriculum_type_id` = `courses`.`curriculum_type_id`
					LEFT JOIN `course_contacts`
					ON `course_contacts`.`course_id` = `courses`.`course_id`
					AND `course_contacts`.`contact_type` = 'director'
					LEFT JOIN `".AUTH_DATABASE."`.`user_data`
					ON `".AUTH_DATABASE."`.`user_data`.`id` = `course_contacts`.`proxy_id`
					WHERE `courses`.`course_active` = '1'
					" . $search_sql . "
			        AND `courses`.`organisation_id` = ?
					GROUP BY `courses`.`course_id`
					" . $order_sql . "
					LIMIT ?, ?";

        $results = $db->GetAll($query, array($organisation_id, $offset, $limit));

        if ($results) {
            return $results;
        }
        return false;
    }

    public function getFullAccessCourses($proxy_id, $organisation_id, $search_term = "", $offset = null, $limit = null, $sort_column = null, $sort_direction = null){
        global $db;

        $sort_columns_array = array(
            "type" => "`curriculum_lu_types`.`curriculum_type_name`",
            "name" => "`a`.`course_name`",
            "code" => "`a`.`course_code`",
        );

        $order_sql = " ORDER BY ".$sort_columns_array[$sort_column]. " ".$sort_direction." " ;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " AND ( `a`.`course_name` LIKE (". $db->qstr($search_term) . ")
                            OR `a`.`course_code` LIKE (". $db->qstr($search_term) . ")
                            ) ";
        }

        $query = "	SELECT `a`.`course_id`, `a`.`course_name`, `a`.`course_code`, `curriculum_lu_types`.`curriculum_type_name`
                            FROM `courses` AS a
                            LEFT JOIN `course_contacts` AS b
                            ON b.`course_id` = a.`course_id`
                            AND b.`proxy_id` = ?
                            AND b.`contact_type` = 'director'
                            LEFT JOIN `community_courses` AS c
                            ON c.`course_id` = a.`course_id`
                            LEFT JOIN `curriculum_lu_types`
					        ON `curriculum_lu_types`.`curriculum_type_id` = `a`.`curriculum_type_id`
                            LEFT JOIN `community_members` AS d
                            ON d.`community_id` = c.`community_id`
                            AND d.`proxy_id` = ?
                            WHERE
                            (
                                a.`pcoord_id` = ?
                                OR b.`proxy_id` = ?
                                OR d.`member_acl` = '1'
                            )
                            " . $search_sql . "
                             AND `a`.`organisation_id` = ?
                            AND a.`course_active` = '1'
                            " . $order_sql . "
                            LIMIT ?, ?";

        $results = $db->GetAll($query, array($proxy_id, $proxy_id, $proxy_id, $proxy_id, $organisation_id, $offset, $limit));
        if ($results) {
            return $results;
        }
        return false;
    }

    public static function fetchCourseForContact($organisation_id, $sort_by, $sort_direction, $limit, $offset, $search_term) {
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " 
                            AND ( a.`course_name` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            OR a.`course_code` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            ) 
                          ";
        }

        $sort_columns_array = array(
            "type" => "`curriculum_type_name`",
            "name" => "`course_name`",
            "code" => "`course_code`",
        );

        $query	= "	SELECT *
					FROM (
					SELECT a.`course_id`, a.`organisation_id`, a.`course_name`, a.`course_code`, a.`course_url`, 
                    a.`notifications`, c.`curriculum_type_name`
					FROM `courses` AS a
					LEFT JOIN `course_contacts` AS b
					ON b.`course_id` = a.`course_id`
					AND b.`contact_type` = %s
					AND b.`contact_order` = 0
					LEFT JOIN `curriculum_lu_types` AS c
					ON c.`curriculum_type_id` = a.`curriculum_type_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
					ON d.`id` = b.`proxy_id`
					WHERE a.`course_active` = '1'
                    AND a.`organisation_id` = %s";

        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

        $query .= ") x ORDER BY %s %s LIMIT %s OFFSET %s";

        $query = sprintf($query,$db->qstr("director"), $organisation_id, $sort_columns_array[$sort_by], $sort_direction, $limit, $offset);

        $results	= $db->GetAll($query);

        if ($results) {
            return $results;
        }
        return false;
    }

    public static function fetchTotalCountCourseForContact($organisation_id, $search_term) {
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " 
                            AND ( a.`course_name` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            OR a.`course_code` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            ) 
                          ";
        }

        $sort_columns_array = array(
            "type" => "`curriculum_type_name`",
            "name" => "`course_name`",
            "code" => "`course_code`",
        );

        $query	= "	SELECT COUNT(*) as total
					FROM (
					SELECT a.`course_id`, a.`organisation_id`, a.`course_name`, a.`course_code`, a.`course_url`,
                     a.`notifications`, c.`curriculum_type_name` as type
					FROM `courses` AS a
					LEFT JOIN `course_contacts` AS b
					ON b.`course_id` = a.`course_id`
					AND b.`contact_type` = %s
					AND b.`contact_order` = 0
					LEFT JOIN `curriculum_lu_types` AS c
					ON c.`curriculum_type_id` = a.`curriculum_type_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
					ON d.`id` = b.`proxy_id`
					WHERE a.`course_active` = '1'
					AND a.`organisation_id` = %s";

        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

		$query .=   ") x";

        $query = sprintf($query,$db->qstr("director"), $organisation_id);

        $results	= $db->GetAll($query);

        if ($results) {
            return $results;
        }
        return false;
    }

    public static function fetchCourseForGraderAssignmentContact($user_id, $organisation_id, $sort_by, $sort_direction, $limit, $offset, $search_term) {
        global $db;

        $sort_columns_array = array(
            "type" => "`curriculum_type_name`",
            "name" => "`course_name`",
            "code" => "`course_code`",
        );
       
        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " 
                            AND ( a.`course_name` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            OR a.`course_code` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            ) 
                          ";
        }

        $query	= "	SELECT *
					FROM (
							SELECT DISTINCT(a.`course_id`), a.`organisation_id`, a.`course_name`, a.`course_code`, 
							a.`course_url`, a.`notifications`, c.`curriculum_type_name`, 
							CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`
							FROM `courses` AS a
							LEFT JOIN `course_contacts` AS b
							ON b.`course_id` = a.`course_id`
							AND b.`contact_type` = 'director'
							AND b.`contact_order` = 0
							LEFT JOIN `curriculum_lu_types` AS c
							ON c.`curriculum_type_id` = a.`curriculum_type_id`
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = b.`proxy_id`
							LEFT JOIN `community_courses` AS e
							ON e.`course_id` = a.`course_id`
							LEFT JOIN `community_members` AS f
							ON f.`community_id` = e.`community_id`
							AND f.`proxy_id` = ".$db->qstr($user_id)."
							LEFT JOIN `assignments` g
							ON g.`course_id` = a.`course_id`
							WHERE
							(
								a.`pcoord_id` = ".$db->qstr($user_id)."
								OR b.`proxy_id` = ".$db->qstr($user_id)."
								OR f.`member_acl` = '1'
							)
							AND a.`course_active` = '1'
							AND a.`organisation_id` = ".$db->qstr($organisation_id);

        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

		$query .=		"UNION
							SELECT a.`course_id`, a.`organisation_id`, a.`course_name`, a.`course_code`, 
							a.`course_url`, a.`notifications`, c.`curriculum_type_name`, 
							CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`
							FROM `assignment_contacts` b
							JOIN `assignments` bb
							ON b.`assignment_id` = bb.`assignment_id`
							JOIN `courses` a
							ON a.`course_id` = bb.`course_id`
							JOIN `curriculum_lu_types` AS c
							ON a.`curriculum_type_id` = c.`curriculum_type_id`
							JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = b.`proxy_id`
							WHERE b.`proxy_id` = " . $db->qstr($user_id) . "
							AND bb.`assignment_active` = 1";
						
        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

        $query .=       " UNION
							SELECT a.`course_id`, a.`organisation_id`, a.`course_name`, a.`course_code`, 
							a.`course_url`,a.`notifications`, c.`curriculum_type_name`, 
							CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`
							FROM `assessment_graders` b
							JOIN `assessments` bb
							ON bb.`assessment_id` = b.`assessment_id`
							JOIN `courses` a
							ON a.`course_id` = bb.`course_id`
							JOIN `curriculum_lu_types` AS c
							ON a.`curriculum_type_id` = c.`curriculum_type_id`
							JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = b.`grader_proxy_id`
							WHERE b.`grader_proxy_id` = " . $db->qstr($user_id) . "
							AND bb.`active` = 1";
		
        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

        $query .= ") x GROUP BY `course_id` ORDER BY %s %s LIMIT %s OFFSET %s";

        $query		= sprintf($query, $sort_columns_array[$sort_by], $sort_direction, $limit, $offset);

        $results = $db->GetAll($query);

        if ($results) {
            return $results;
        }
        return false;
    }

    public static function fetchTotalCountCourseForGraderAssignmentContact($user_id, $organisation_id, $search_term) {
        global $db;

        $search_sql = "";
        if(!empty($search_term)) {
            $search_sql = " 
                            AND ( a.`course_name` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            OR a.`course_code` LIKE ". $db->qstr("%%".$search_term."%%") . "
                            ) 
                          ";
        }

        $query	= "	SELECT COUNT(*) as total
					FROM (
							SELECT DISTINCT(a.`course_id`), a.`organisation_id`, a.`course_name`, a.`course_code`, a.`course_url`, a.`notifications`, c.`curriculum_type_name`, CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`
							FROM `courses` AS a
							LEFT JOIN `course_contacts` AS b
							ON b.`course_id` = a.`course_id`
							AND b.`contact_type` = 'director'
							AND b.`contact_order` = 0
							LEFT JOIN `curriculum_lu_types` AS c
							ON c.`curriculum_type_id` = a.`curriculum_type_id`
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = b.`proxy_id`
							LEFT JOIN `community_courses` AS e
							ON e.`course_id` = a.`course_id`
							LEFT JOIN `community_members` AS f
							ON f.`community_id` = e.`community_id`
							AND f.`proxy_id` = ".$db->qstr($user_id)."
							LEFT JOIN `assignments` g
							ON g.`course_id` = a.`course_id`
							WHERE
							(
								a.`pcoord_id` = ".$db->qstr($user_id)."
								OR b.`proxy_id` = ".$db->qstr($user_id)."
								OR f.`member_acl` = '1'
							)
							AND a.`course_active` = '1'
							AND a.`organisation_id` = ".$db->qstr($organisation_id);

        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

		$query .=		" UNION
							SELECT a.`course_id`, a.`organisation_id`, a.`course_name`, a.`course_code`, a.`course_url`, a.`notifications`, c.`curriculum_type_name`, CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`
							FROM `assignment_contacts` b
							JOIN `assignments` bb
							ON b.`assignment_id` = bb.`assignment_id`
							JOIN `courses` a
							ON a.`course_id` = bb.`course_id`
							JOIN `curriculum_lu_types` AS c
							ON a.`curriculum_type_id` = c.`curriculum_type_id`
							JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = b.`proxy_id`
							WHERE b.`proxy_id` = " . $db->qstr($user_id) . "
							AND bb.`assignment_active` = 1";

        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

		$query .=		" UNION
							SELECT a.`course_id`, a.`organisation_id`, a.`course_name`, a.`course_code`, a.`course_url`, a.`notifications`, c.`curriculum_type_name`, CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`
							FROM `assessment_graders` b
							JOIN `assessments` bb
							ON bb.`assessment_id` = b.`assessment_id`
							JOIN `courses` a
							ON a.`course_id` = bb.`course_id`
							JOIN `curriculum_lu_types` AS c
							ON a.`curriculum_type_id` = c.`curriculum_type_id`
							JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = b.`grader_proxy_id`
							WHERE b.`grader_proxy_id` = " . $db->qstr($user_id) . "
							AND bb.`active` = 1";

        if (!empty($search_sql)) {
            $query .= $search_sql;
        }

		$query .=		") x";

        $results	= $db->GetAll($query);

        if ($results) {
            return $results;
        }
        return false;
    }
}

?>
