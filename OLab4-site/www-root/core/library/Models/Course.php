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
            $cbme_milestones,
            $created_date,
            $created_by,
            $updated_date,
            $updated_by,
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

    public function getCBMEMilestones () {
        return $this->cbme_milestones;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate() {
        return $this->created_date;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy() {
        return $this->created_by;
    }

    /**
     * @return mixed
     */
    public function getUpdatedDate() {
        return $this->updated_date;
    }

    /**
     * @return mixed
     */
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCourseText() {
        if ($this->getCourseCode()) {
            return sprintf("%s: %s", $this->getCourseCode(), $this->getCourseName());
        } else {
            return $this->getCourseName();
        }
    }
    
    /* @return bool|Models_Community */
    public function getCommunity() {
        if ($this->community != null) {
            return $this->community;
        } else {
            $course_community = Models_Community_Course::fetchRowByCourseID($this->course_id);
            if (isset($course_community) && is_object($course_community)) {
                return $this->community = Models_Community::fetchRowByID($course_community->getCommunityID());
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
        // Sort by course name
        return $self->fetchAll(array("organisation_id" => $org_id, "course_active" => "1"), "=", "AND", "course_name");
    }

    public static function fetchAllByOrgSortByName($org_id) {
        $self = new self();
        return $self->fetchAll(array("organisation_id" => $org_id, "course_active" => "1"), "=", "AND", "course_name", "ASC");
    }

    // Only return courses that have events
    public static function getCoursesThatHaveEventsByOrg($org_id) {
        global $db;

        $query = 'SELECT c.* FROM courses c'
            . ' JOIN events e ON e.course_id = c.course_id'
            . ' WHERE c.`course_active`="1"'
            . ' AND `organisation_id` = ' . $org_id
            . ' ORDER BY c.`course_code`, c.`course_name`';

        return $db->GetAll($query);
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

    public function getCperiodID() {
        global $ENTRADA_USER;
        $user_id = $ENTRADA_USER->getID();
        $course_audiences = Models_Course_Audience::fetchAudienceByUserID($this->getID(), $user_id);
        if ($course_audiences) {
            $first_course_audience = current($course_audiences);
            $cperiod_id = $first_course_audience->getCperiodID();
        } else {
            $curriculum_periods = Models_Curriculum_Period::fetchAllByDateCourseID(time(), $this->getID());
            if ($curriculum_periods) {
                $first_curriculum_period = current($curriculum_periods);
                $cperiod_id = $first_curriculum_period->getCperiodID();
            } else {
                $cperiod_id = null;
            }
        }
        return $cperiod_id;
    }

    public function getObjectives($cperiod_id) {
        $objective_repository = Models_Repository_Objectives::getInstance();
        $objectives_by_course = $objective_repository->fetchAllByCourseIDsAndCperiodID(array($this->getID()), $cperiod_id);
        if (isset($objectives_by_course[$this->getID()])) {
            return $objectives_by_course[$this->getID()];
        } else {
            return array();
        }
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

    public function getCurriculumMapVersion($cperiod_id) {
        $version_repository = Models_Repository_CurriculumMapVersions::getInstance();
        $versions = $version_repository->fetchVersionsByCourseIDCperiodID($this->getID(), $cperiod_id);
        if ($versions) {
            $first = current($versions);
            return $first;
        } else {
            return null;
        }
    }

    /**
     * @return array[from_objective_id][to_objective_id] = Models_Objective
     */
    public function getLinkedObjectives($version_id, $objectives) {
        $objective_ids = array_map(function (Models_Objective $objective) { return $objective->getID(); }, $objectives);
        $context = new Entrada_Curriculum_Context_Specific_Course($this->getID());
        $objective_repository = Models_Repository_Objectives::getInstance();
        $objectives_by_version = $objective_repository->fetchLinkedObjectivesByIDs("from", $objective_ids, $version_id, $context);

        // Flatten the array by one level to return linked objectives for this version only
        return $objective_repository->flatten($objectives_by_version);
    }

    public function updateLinkedObjectives(array $objectives, array $linked_objectives, $version_id) {
        $context = new Entrada_Curriculum_Context_Specific_Course($this->getID());
        return Models_Repository_Objectives::getInstance()->updateLinkedObjectives($objectives, $linked_objectives, $version_id, $context);
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

    //TODO: Change this to not depend on $ENTRADA_USER
    public static function getUserCourses($proxy_id, $organisation_id, $search_value = null, $active = 1, $order_by_course_code = false) {
        global $db, $ENTRADA_USER, $ENTRADA_ACL;
        $courses = false;
        $admin = false;
        if ($ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true)
            || ($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")
        ) {
            $admin = true;
        }
        if ($admin) {
            $query = "      SELECT * FROM `courses`
                            WHERE `organisation_id` = ?
                            AND `course_active` = ? ";
            if($search_value != null) {
                $query .= " AND `course_name` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                            OR `course_code` LIKE (" . $db->qstr("%" . $search_value . "%") . ")";
            }

            $query .= "      GROUP BY `course_id`";

            if ($order_by_course_code) {
                $query .= "  ORDER BY `course_code`";
            } else {
                $query .= "  ORDER BY `course_name`";
            }

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
            $query .= "     GROUP BY a.`course_id`";

            if ($order_by_course_code) {
                $query .= "  ORDER BY a.`course_code`";
            } else {
                $query .= "  ORDER BY a.`course_name`";
            }

            $results = $db->GetAll($query, array($proxy_id, $proxy_id, $organisation_id, $active));
        }
        if ($results) {
            foreach ($results as $result) {
               $courses [] = new self($result);
            }
        }
        
        return $courses;
    }

    /**
     * Fetch a list of the proxy/contact types for a given course.
     *
     * @param $course_id
     * @return mixed
     */
    public static function fetchCourseOwnerList ($course_id) {
        global $db;
        $entrada_auth = AUTH_DATABASE;
        $query = "  SELECT c.`course_id`, cc.`proxy_id`, cc.`contact_type`
                    FROM `course_contacts` AS cc
                    LEFT JOIN `courses` AS c
                    ON c.`course_id` = cc.`course_id`
                    WHERE c.`course_id` = ?
                    AND (cc.`contact_type` = 'director' OR cc.`contact_type` = 'ccoordinator' OR cc.`contact_type` = 'pcoordinator')

                    UNION ALL

                    SELECT uc.`course_id`, uc.`pcoord_id` AS `proxy_id`, 'pcoordinator'
                    FROM `courses` AS uc
                    LEFT JOIN `{$entrada_auth}`.`user_data` AS u
                    ON uc.`pcoord_id` = u.`id`
                    WHERE uc.`course_id` = ?";
        $results = $db->GetAll($query, array($course_id, $course_id));
        if (!$results) {
            return array();
        }
        return $results;
    }


    // TODO: Change this to not depend on $ENTRADA_USER or $ENTRADA_ACL.
    public static function getActiveUserCoursesIDList() {
        global $db, $ENTRADA_USER, $ENTRADA_ACL;

        $course_list = array();
        $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
        $AND_WHERE = $JOIN_COURSE_CONTACTS = "";

        if (!$admin) {
            $JOIN_COURSE_CONTACTS = "   JOIN `course_contacts` AS b
                                        ON a.`course_id` = b.`course_id` ";
            $proxy_id = $ENTRADA_USER->getActiveId();
            $AND_WHERE = "  AND b.`proxy_id` = $proxy_id 
                            AND b.`contact_type` IN ('pcoordinator','director','ccoordinator') ";
        }

        $query = "  
                SELECT a.* FROM `courses` AS a
                $JOIN_COURSE_CONTACTS
                WHERE a.`organisation_id` = ?
                AND a.`course_active` = 1 
                $AND_WHERE ";

        $results = $db->GetAll($query, array($ENTRADA_USER->getActiveOrganisation()));

        if ($results) {
            foreach ($results as $result) {
                $course_list[] = $result["course_id"];
            }
        }

        return $course_list;
    }

    /**
     * Get a list of courses for a specific user.
     * $admin is a true/false flag based on an ACL check done before calling this method.
     * @param $proxy_id
     * @param $organisation_id
     * @param $admin
     * @return array
     */
    public static function getUserCoursesList($proxy_id, $organisation_id, $admin) {
        global $db;
        $AND_WHERE = $JOIN_COURSE_CONTACTS = "";

        if (!$admin) {
            $JOIN_COURSE_CONTACTS = "   JOIN `course_contacts` AS b
                                        ON a.`course_id` = b.`course_id` ";
            $AND_WHERE = "  AND b.`proxy_id` = $proxy_id 
                            AND b.`contact_type` IN ('pcoordinator','director','ccoordinator') ";
        }

        $query = "SELECT a.* FROM `courses` AS a
                $JOIN_COURSE_CONTACTS
                WHERE a.`organisation_id` = ?
                AND a.`course_active` = 1 
                $AND_WHERE ";

        $results = $db->GetAll($query, array($organisation_id));
        return $results;
    }

    public static function getActiveUserCoursesByProxyIDOrganisationID($proxy_id, $organisation_id) {
        global $db;
        $course_list = array();

        $query = "  SELECT a.* FROM `courses` AS a
                    JOIN `course_contacts` AS b
                    ON a.`course_id` = b.`course_id`
                    WHERE b.`proxy_id` = ?
                    AND a.`organisation_id` = ?
                    AND a.`course_active` = 1
                    AND b.`contact_type` IN ('pcoordinator','director','ccoordinator')
                    GROUP BY b.`course_id`
                    ORDER BY a.`course_code`, a.`course_name` ASC";

        $results = $db->GetAll($query, array($proxy_id, $organisation_id));

        if ($results) {
            foreach ($results as $result) {
                $self = new self();
                $course_list[] = $self->fromArray($result);
            }
        }
        return $course_list;
    }

    public static function fetchAllByIDs ($course_ids = array(), $active = 1) {
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
        
        $results = $db->GetRow($query, array($course_id, $active));
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

    /**
     * Return a list of course for a resident/user
     * Based on the courses_get_courses function in functions.inc.php
     *
     * @param $proxy_id
     * @param $organisation_id
     * @param $cperiod_ids
     * @return mixed
     */
    public static function getCoursesByProxyIDOrganisationID($proxy_id, $organisation_id, $cperiod_ids = null, $active_only = false) {
        global $db;

        $active_only = (bool) $active_only;

        $query = "  SELECT DISTINCT(a.`course_id`), a.`course_name`, a.`course_code`, a.`course_active`, a.`organisation_id`
                    FROM `courses` AS a
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
                    )";

        if ($active_only) {
            $query .= " AND a.`course_active` = 1";
        }

        if ($cperiod_ids) {
            $query .= " AND b.`cperiod_id` IN (".implode(", ", $cperiod_ids).")";
        }

        return $db->GetAll($query, array($organisation_id, $proxy_id, $proxy_id));
    }

    /**
     * Return a list of courses for a faculty member
     * @param $proxy_id
     * @param $organisation_id
     * @return mixed
     */
    public static function getCoursesByContacts($proxy_id, $organisation_id) {
        global $db;

        $query = "SELECT DISTINCT(a.`course_id`), a.`course_name`, a.`course_code`, a.`course_active`, a.`organisation_id`
                  FROM `courses` as a
                  INNER JOIN `course_contacts` AS b
                  ON a.`course_id` = b.`course_id`
                  WHERE b.`proxy_id` = ?
                  AND a.`organisation_id` = ?";

        return $db->getAll($query, array($proxy_id, $organisation_id));
    }

    /*
     * Get the user's selected course preference and a list of their courses
     *
     * @param $limit_to_current_cperiod
     *
     * @return array
     */
    public function getCurrentUserCourseList($limit_to_current_cperiod) {
        global $ENTRADA_USER;

        /**
         * Fetch all user courses
         */
        $cperiod_ids = array();
        if ($limit_to_current_cperiod) {
            $cperiod_model = new Models_Curriculum_Period();
            $cperiod_ids = $cperiod_model->fetchAllCurrentIDsByOrganisation($ENTRADA_USER->getActiveOrganisation());
        }
        $courses = Models_Course::getCoursesByProxyIDOrganisationID($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $cperiod_ids, true);

        return $courses;
    }

    /**
     * return the total number of courses for a specific course parent.
     * @param null $course_id
     * @param int $active
     * @return int
     */
    public static function countCourseChildren ($course_id = null, $active = 1) {
        global $db;

        $query = "  SELECT COUNT(`course_id`) AS `total_children` FROM `courses`
                    WHERE `parent_id` = ?
                    AND `course_active` = ?";

        $result = $db->GetRow($query, array($course_id, $active));

        if ($result) {
            return $result["total_children"];
        } else {
            return 0;
        }

    }

    /**
     * Get the current users courses as a target list for the Advanced Search widget
     * @param $search_value
     * @return string
     */
    public static function getUserCoursesAsTargets($search_value) {
        global $ENTRADA_USER, $translate;

        $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $search_value);
        if ($user_courses) {
            $data = array();
            foreach ($user_courses as $course) {
                $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName(), "target_parent" => $course->getParentID(), "target_children" => Models_Course::countCourseChildren($course->getParentID()), "course_id" => $course->getID(), "level_selectable" => 1);
            }
            return json_encode(array("status" => "success", "data" => $data, "parent_name" => "0"));
        } else {
            return json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
        }
    }

    /**
     * @param $proxy_id
     * @param $contact_type
     * @return array
     */
    public function fetchAllCoursesByProxyIDContactType($proxy_id, $contact_type) {
        global $db;
        $courses = array();

        $query = "  SELECT co.* FROM ".$this::$table_name." AS co
                    JOIN `course_contacts` AS cc
                    ON cc.`course_id` = co.`course_id`
                    WHERE cc.`proxy_id` = ?
                    AND cc.`contact_type` = ?";
        $results = $db->GetAll($query, array($proxy_id, $contact_type));

        if ($results) {
            foreach ($results as $result) {
                $courses[] = new self($result);
            }
        }

        return $courses;
    }

    /**
     * @param $course_id
     * @param $proxy_id
     * @param $contact_type
     * @return array
     */
    public function fetchRowByCourseIDProxyIDContactType($course_id, $proxy_id, $contact_type) {
        global $db;
        $course = null;

        $query = "  SELECT co.* FROM ".$this::$table_name." AS co
                    JOIN `course_contacts` AS cc
                    ON cc.`course_id` = co.`course_id`
                    WHERE cc.`proxy_id` = ?
                    AND cc.`contact_type` = ?
                    AND cc.`course_id` = ?";
        $result = $db->GetRow($query, array($proxy_id, $contact_type, $course_id));

        if ($result) {
            $course = new self($result);
        }

        return $course;
    }

    /*
     * Fetch all courses based on a users course_group_contact records
     * @param $proxy_id
     */
    public function fetchAllByCourseGroupContact($proxy_id) {
        global $db;
        $courses = array();
        $query = "  SELECT co.*, cg.`cgroup_id` FROM `".$this::$table_name."` AS co
                    JOIN `course_groups` as cg
                    ON co.`course_id` = cg.`course_id`
                    JOIN `course_group_contacts` as cgc
                    ON cg.`cgroup_id` = cgc.`cgroup_id`
                    WHERE cgc.`proxy_id` = ?
                    AND co.`course_active` = 1
                    AND cg.`active` = 1";
        $results = $db->GetAll($query, array($proxy_id));
        if ($results) {
            $courses = $results;
        }
        return $courses;
    }
}
