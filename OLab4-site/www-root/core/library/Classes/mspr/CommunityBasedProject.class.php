<?php

require_once("SupervisedProject.class.php");

class CommunityBasedProject extends SupervisedProject {
	/**
	 * 
	 * @param User $user
	 * @return CommunityHealthAndEpidemiology
	 */
	public static function get($id) {
		global $db;
		if ($id instanceof User) {
			$id = $id->getID();
		}
		
		$query		= "SELECT * FROM `student_community_health_and_epidemiology` WHERE `user_id`=?";
		$result = $db->getRow($query, array($id));
		if ($result) {
			$comm_health =  self::fromArray($result);
			return $comm_health;
		} 
	} 

	/**
	 * Creates new project object from array
	 * @param array $arr
	 * @return CommunityHealthAndEpidemiology
	 */
	public static function fromArray(array $arr) {
		return new self($arr['user_id'], $arr['title'], $arr['organization'], $arr['location'], $arr['supervisor'], $arr['comment'],$arr['status']);
	} 

	/**
	 * Creates a new Community Health and Epidemiology entry OR updates if one already exists. This will reset the approval.
	 * @param unknown_type $user
	 * @param unknown_type $title
	 * @param unknown_type $organization
	 * @param unknown_type $location
	 * @param unknown_type $supervisor
	 */
	public static function create(array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "insert into `student_community_health_and_epidemiology` 
					(`user_id`, `title`, `organization`,`location`,`supervisor`, `status`)
					value (?, ?, ?, ?, ?, IFNULL(?,0))";
		if(!$db->Execute($query, array($user_id, $title, $organization, $location, $supervisor, $status))) {
			add_error("Failed to update Community-Based Project entry.");
			application_log("error", "Unable to update a student_community_health_and_epidemiology record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated Community-Based Project entry.");
		}
	}
	
	private function setStatus($status_code, $comment=null) {
		global $db;
		$query = "update `student_community_health_and_epidemiology` set `status`=?, `comment`=? where `user_id`=?";
		if(!$db->Execute($query, array($status_code, $comment, $this->getUserID()))) {
			add_error("Failed to update Community-Based Project entry.");
			application_log("error", "Unable to update a student_community_health_and_epidemiology record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated Community-Based Project entry.");
		}
	}
	
	public function approve() {
		$this->setStatus(1);
	}
	
	public function unapprove() {
		$this->setStatus(0);
	}
	public function reject($comment) {
		$this->setStatus(-1, $comment);
	}
	
	public function update(array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "update `student_community_health_and_epidemiology` set `title`=?, `organization`=?, `location`=?, `supervisor`=?, `status`=?, `comment`=? where `user_id`=?";
		if(!$db->Execute($query, array($title, $organization, $location, $supervisor, $status, $comment, $this->getID()))) {
			add_error("Failed to update Community-Based Project.");
			application_log("error", "Unable to update a student_community_health_and_epidemiology record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated Community-Based Project.");
		}
	}
	
	public function delete() {
		global $db;
		$query = "DELETE FROM `student_community_health_and_epidemiology` where `user_id`=?";
		if(!$db->Execute($query, array($this->getID()))) {
			add_error("Failed to remove Community-Based Project.");
			application_log("error", "Unable to delete a student_community_health_and_epidemiology record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed Community-Based Project.");
		}		
	}
}