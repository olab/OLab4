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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

/**
 * Course class with all information. Methods referring to other classes are not all complete.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class Course {
	private $course_id,
			$curriculum_type_id,
			$director_id,
			$pcoord_id,
			$evalrep_id,
			$studrep_id,
			$course_name,
			$course_code,
			$course_description,
			$unit_collaborator,
			$unit_communicator,
			$unit_health_advocate,
			$unit_manager,
			$unit_professional,
			$unit_scholar,
			$unit_medical_expert,
			$unit_summative_assessment,
			$unit_formative_assessment,
			$unit_grading,
			$resources_required,
			$resources_optional,
			$course_url,
			$course_message,
			$notifications,
			$organization,
			$active;

	function __construct(	$course_id,
							$curriculum_type_id,
							$director_id,
							$pcoord_id,
							$evalrep_id,
							$studrep_id,
							$course_name,
							$course_code,
							$course_description,
							$unit_collaborator,
							$unit_communicator,
							$unit_health_advocate,
							$unit_manager,
							$unit_professional,
							$unit_scholar,
							$unit_medical_expert,
							$unit_summative_assessment,
							$unit_formative_assessment,
							$unit_grading,
							$resources_required,
							$resources_optional,
							$course_url,
							$course_message,
							$notifications,
							$organization,
							$active
							) {

		$this->course_id = $course_id;
		$this->curriculum_type_id = $curriculum_type_id;
		$this->director_id = $director_id;
		$this->pcoord_id = $pcoord_id;
		$this->evalrep_id = $evalrep_id;
		$this->studrep_id = $studrep_id;
		$this->course_name = $course_name;
		$this->course_code = $course_code;
		$this->course_description = $course_description;
		$this->unit_collaborator = $unit_collaborator;
		$this->unit_communicator = $unit_communicator;
		$this->unit_health_advocate = $unit_health_advocate;
		$this->unit_manager = $unit_manager;
		$this->unit_professional = $unit_professional;
		$this->unit_scholar = $unit_scholar;
		$this->unit_medical_expert = $unit_medical_expert;
		$this->unit_summative_assessment = $unit_summative_assessment;
		$this->unit_formative_assessment = $unit_formative_assessment;
		$this->unit_grading = $unit_grading;
		$this->resources_required = $resources_required;
		$this->resources_optional = $resources_optional;
		$this->course_url = $course_url;
		$this->course_message = $course_message;
		$this->notifications = $notifications;
		$this->organization = $organization;
		$this->active = $active;
		//be sure to cache this whenever created.
		$cache = SimpleCache::getCache();
		$cache->set($this,"Course",$this->course_id);
	}
	
	/**
	 * Returns the id of the user
	 * @return int
	 */
	public function getID() {
		return $this->course_id;
	}
	
	/**
	 * returns the User object for the Director of this course
	 *@return User
	 */
	public function getDirector(){
		return User::fetchRowByID($this->director_id);
	}
	
	/**
	 * returns the User object for the Coordinator for this course
	 *@return User
	 */
	public function getPCoordinator() {
		return User::fetchRowByID($this->pcoord_id);
	}
	
	/**
	 * returns the User object for the evaluation rep for this course
	 *@return User
	 */
	public function getEvalRep() {
		return User::fetchRowByID($this->evalrep_id);
	}
	
	/**
	 * returns the User object for the student rep for this course
	 *@return User
	 */
	public function getStudentRep() {
		return User::fetchRowByID($this->studrep_id);
	}
	
	/**
	 * @return string
	 */
	public function getCourseName() {
		return $this->course_name;
	}
	
	/**
	 * Alias of getCourseName()
	 * @return string
	 */
	public function getTitle() {
		return $this->getCourseName();
	}
	
	/**
	 * Return course code
	 * @return string
	 */
	public function getCourseCode() {
		return $this->course_code;
	}
	
	/**
	 * Returns the course description
	 * @return string
	 */
	public function getDescription() {
		return $this->course_description;
	}
	
	public function getCurriculumType() {
		//TODO add Curriculum Layout	
	}
	
	public function getObjectives() {
		//TODO add objective request after Objectives class
	}
	
	/**
	 * Returns value of unit_collaborator field
	 * @return string
	 */
	public function getUnitCollaborator() {
		return $this->unit_collaborator;
	}
	
	/**
	 * Returns value of unit_communicator field
	 * @return string
	 */
	public function getUnitCommunicator() {
		return $this->unit_communicator;
	}
	
	/**
	 * Returns value of unit_health_advocate field
	 * @return string
	 */
	public function getUnitHealthAdvocate() {
		return $this->unit_health_advocate;
	}
	
	/**
	 * Returns value of unit_manager field
	 * @return string
	 */
	public function getUnitManager() {
		return $this->unit_manager;
	}
	
	/**
	 * Returns value of unit_professional field
	 * @return string
	 */
	public function getUnitProfessional() {
		return $this->unit_professional;
	}
	
	/**
	 * Returns value of unit_scholar field
	 * @return string
	 */
	public function getUnitScholar() {
		return $this->unit_scholar;
	}
	
	/**
	 * Returns value of unit_medical_expert field
	 * @return string
	 */
	public function getUnitMedicalExpert() {
		return $this->unit_medical_expert;
	}

	/**
	 * Returns value of unit_summative_assessment field
	 * @return string
	 */
	public function getUnitSummativeAssessment() {
		return $this->unit_summative_assessment;
	}
	
	/**
	 * Returns value of unit_formative_assessment field
	 * @return string
	 */
	public function getUnitFormativeAssessment() {
		return $this->unit_formative_assessment;
	}
	
	/**
	 * Returns value of unit_grading field
	 * @return string
	 */
	public function getUnitGrading() {
		return $this->unit_grading;
	}
	
	/**
	 * Returns required resource information for this course
	 * @return string
	 */
	public function getResourcesRequired() {
		return $this->resources_required;
	}
	
	/**
	 * Returns the optional resource information for this course
	 * @return string
	 */
	public function getResourcesOptional() {
		return $this->resources_optional;
	}
	
	/**
	 * Returns course web page url
	 * @return string
	 */
	public function getURL() {
		return $this->courrse_url;
	}
	
	/**
	 * Returns the message set for this course
	 * @return string
	 */
	public function getCourseMessage() {
		return $this->course_message;
	}
	
	/**
	 * Returns true if the course is active; false, otherwise
	 * @return bool
	 */
	public function isActive() {
		return $this->active === 1;
	}
	
	/**
	 * Returns true if there are notifications for this course; false, otherwise
	 * @return boolean
	 */
	public function hasNotifications() {
		return $this->notifications === 1;
	}
	
	/**
	 * Returns true if the provided user qualifies as an owner of the course. Owners are users specified as course director or program coordinator 
	 * @param User $user
	 * @return boolean
	 */
	public function isOwner(User $user) {
		$user_id = $user->getID();
		return (($user_id == $this->director_id) || ($user_id == $this->pcoord_id));
	}
	
	/**
	 * Returns the Organisation under which this course belongs 
	 * @return Organisation
	 */
	public function getOrganization() {
		return Organisation::get($this->organization);
	}
	
	public function getOrganisationID() {
		return $this->organization;
	}
	
	public function getContacts($course_id = NULL) {
		global $db;
		
		if (is_null($course_id)) {
			if (!is_null($this->course_id)) {
				$course_id = $this->course_id;
			} else {
				return false;
			}
 		}
		
		$query = "SELECT * FROM `course_contacts` WHERE `course_id` = ?";
		$results = $db->GetAll($query, array($course_id));
		if (is_array($results)) {
			foreach ($results as $result) {
				$u = new User;
				$c[$result["contact_type"]][$result["proxy_id"]] = $u->fetchRowByID($result["proxy_id"]);
			}
			return $c;
		} else {
			return false;
		}
	}
	
	public function getPages($course_id = NULL, $whitelist = array()) {
		global $db;

		if (is_null($course_id)) {
			if (!is_null($this->course_id)) {
				$course_id = $this->course_id;
			} else {
				return false;
			}
 		}
		
		$p = false;
		
		$query = "	SELECT a.*
					FROM `community_pages` AS a
					JOIN `community_courses` AS b
					ON a.`community_id` = b.`community_id`
					WHERE b.`course_id` = ?".
					(!empty($whitelist) ? " AND `page_url` IN ('".implode("','", $whitelist)."')" : "")."
					ORDER BY IF (a.`page_order` != 0, a.`page_order`, a.`parent_id`), a.`page_url`";
		$results = $db->GetAssoc($query, array($course_id));
		
		return $results;
		
	}
	
	public function getEvents($start, $end) {
		global $db;
				
		$e = array();
		
		$query = "	SELECT a.* 
					FROM `events` AS a
					WHERE a.`course_id` = ?
					AND a.`event_start` BETWEEN ? AND ?
					ORDER BY a.`event_start`";
		$results = $db->GetAll($query, array($this->course_id, $start, $end));
		if ($results) {
			foreach ($results as $key => $event) {
				$e[$key] = $event;
				$query = "	SELECT b.`objective_id`, b.`objective_name` 
							FROM `event_objectives` AS a 
							JOIN `global_lu_objectives` AS b
							ON a.`objective_id` = b.`objective_id`
							WHERE a.`event_id` = ?";
				$event_objectives = $db->GetAssoc($query, $event["event_id"]);
				if ($event_objectives) {
					$e[$key]["objectives"] = $event_objectives;
				}
			}
			return $e;
		} else {
			return false;
		}
	}
	
	/**
	 * Returns the Course belonging to the specified ID
	 * @param int $course_id
	 * @return Course
	 */
	public static function get($course_id) {
		$cache = SimpleCache::getCache();
		$course = $cache->get("Course",$course_id);
		if (!$course) {
			global $db;
			$query = "SELECT * FROM `courses` WHERE `course_id` = ".$db->qstr($course_id);
			$result = $db->getRow($query);
			if ($result) {
				$course =  self::fromArray($result);			
			}		
		} 
		return $course;
	}
	
	/**
	 * Creates a course from supplied array 
	 * @param unknown_type $arr
	 * @return Course
	 */
	public static function fromArray($arr) {
		return new Course($arr['course_id'],$arr['curriculum_type_id'],$arr['director_id'],$arr['pcoord_id'],$arr['evalrep_id'],$arr['studrep_id'],$arr['course_name'],$arr['course_code'],$arr['course_description'],$arr['unit_collaborator'],$arr['unit_communicator'],$arr['unit_health_advocate'],$arr['unit_manager'],$arr['unit_professional'],$arr['unit_scholar'],$arr['unit_medical_expert'],$arr['unit_summative_assessment'],$arr['unit_formative_assessment'],$arr['unit_grading'],$arr['resources_required'],$arr['resources_optional'],$arr['course_url'],$arr['course_message'],$arr['notifications'],$arr['organisation_id'],$arr['course_active']);
	}
	
}