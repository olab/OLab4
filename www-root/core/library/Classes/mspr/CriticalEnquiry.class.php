<?php

require_once("SupervisedProject.class.php");

class CriticalEnquiry extends SupervisedProject {
	/**
	 * 
	 * @param User $user
	 * @return CriticalEnquiry
	 */
	public static function get($id) {
		global $db;
		if ($id instanceof User) {
			$id = $id->getID();
		}
		$query		= "SELECT * FROM `student_critical_enquiries` WHERE `user_id`=?";
		$result = $db->getRow($query, array($id));
		if ($result) {
			$critical_enquiry = self::fromArray($result);
			return $critical_enquiry;
		}
	} 
	
	/**
	 * Creates new project object from array
	 * @param array $arr
	 * @return CriticalEnquiry
	 */
	public static function fromArray(array $arr) {
		return new self($arr['user_id'], $arr['title'], $arr['organization'], $arr['location'], $arr['supervisor'], $arr['comment'],$arr['status']);
	} 
	

	/**
	 * Creates a new Critical Enquiry entry OR updates if one already exists. This will reset the approval.
	 * @param unknown_type $user
	 * @param unknown_type $title
	 * @param unknown_type $organization
	 * @param unknown_type $location
	 * @param unknown_type $supervisor
	 */
	public static function create(array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "insert into `student_critical_enquiries` 
					(`user_id`, `title`, `organization`,`location`,`supervisor`, `status`)
					value (?, ?, ?, ?, ?, IFNULL(?,0))";
		if(!$db->Execute($query, array($user_id, $title, $organization, $location, $supervisor, $status))) {
			add_error("Failed to create Critical Enquiry entry.");
			application_log("error", "Unable to create a student_critical_enquiries record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully created Critical Enquiry entry.");
		}
	}
	
	public function setStatus($status_code, $comment=null) {
		global $db;
		$query = 	"update `student_critical_enquiries` set 
					`status`=?, `comment`=?
					where `user_id`=?";
		if(!$db->Execute($query, array($status_code, $comment, $this->getUserID()))) {
			add_error("Failed to update Critical Enquiry entry.");
			application_log("error", "Unable to update a student_critical_enquiries record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated Critical Enquiry entry.");
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
		$query = "update `student_critical_enquiries` set `title`=?, `organization`=?, `location`=?, `supervisor`=?, `status`=?, `comment`=? where `user_id`=?";
		$comment = ""; //clear the comment. XXX should this be retained?
		if(!$db->Execute($query, array($title, $organization, $location, $supervisor, $status, $comment, $this->getID()))) {
			add_error("Failed to update Critical Enquiry.");
			application_log("error", "Unable to update a student_critical_enquiries record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated Critical Enquiry.");
		}
	}
	
	public function delete() {
		global $db;
		$query = "DELETE FROM `student_critical_enquiries` where `user_id`=?";
		if(!$db->Execute($query, array($this->getID()))) {
			add_error("Failed to remove Critical Enquiry.");
			application_log("error", "Unable to delete a student_critical_enquiries record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed Critical Enquiry.");
		}		
	}
}