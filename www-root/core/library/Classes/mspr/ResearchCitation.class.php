<?php

/**
 * Class for MSPR listing of Research projects in citation form. Citations are supposed to adhere to MLA guidelines however they are not enforced in this class
 * Priority property allows students to set their preference for appearance in the MSPR. At this time, a maximum of 6 Research citations will be included in the 
 * MSPR, AND since this is student input we need to get staff approval for inclusion, there is potential for students to end up with a sub-optimal listing if we 
 * had a strict limit of 6 citations and some of them were not approved.       
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */

require_once("Classes/utility/Approvable.interface.php");
require_once("Classes/utility/AttentionRequirable.interface.php");
require_once("Classes/utility/Editable.interface.php");

class ResearchCitation implements Approvable, AttentionRequirable, Editable {
	private $id;
	private $user_id;
	private $citation;
	private $priority;
	private $status;
	private $comment;
	
	function __construct($id, $user_id, $citation, $priority, $comment, $status=0) {
		$this->id = $id;
		$this->user_id = $user_id;
		$this->citation = $citation;
		$this->priority = $priority;
		$this->comment = $comment;
		$this->status = $status;
	}
	
	public static function fromArray(array $arr) {
		return new self($arr['id'], $arr['user_id'], $arr['citation'], $arr['priority'], $arr['comment'], $arr['status']);
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function getUserID() {
		return $this->user_id;	
	}
	
	public function getUser() {
		return User::fetchRowByID($this->user_id);
	}
	
	/**
	 * Returns the text of the citation
	 * @return string
	 */
	public function getText() {
		return $this->citation;
	}
	
	public function isAttentionRequired() {
		return !$this->isApproved() && !$this->isRejected();
	}
	
	/**
	 * Returns the priority of the citation 
	 */
	public function getPriority() {
		return $this->priority;
	}
	
	public function isApproved() {
		return ($this->status == 1);
	}
	
	public function isRejected() {
		return ($this->status == -1);
	}
	
	public function getComment() {
		return $this->comment;
	}
	
		
	/**
	 * Returns a single ResearchCitation if found
	 * @param int $id
	 * @return ResearchCitation
	 */
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `student_research` WHERE `id` = ".$db->qstr($id);
		$result = $db->getRow($query);
		if ($result) {
			
			$citation = self::fromArray($result);
			return $citation;
		}
	} 
	
	/**
	 * Returns the next priority number. 0 if there are no eistent entires for this user, and max+1 otherwise.
	 * @param $user_id
	 */
	private static function getNewPriority($user_id) {
		global $db;
		$query = "select MAX(`priority`) + 1 as hp from student_research where user_id=".$db->qstr($user_id)." group by `user_id`";
		$result = $db->getRow($query);
		if (!$result) {
			$priority = 0;
		} else {
			$priority = $result['hp'];
		}
		return $priority;
	}
	
	/**
	 * Adds a new citation and sets the priority at the end of the list.  
	 * @param $user_id
	 * @param $citation
	 * @param $approved
	 */
	public static function create(array $input_arr) {
		extract($input_arr);
		global $db;
		$priority = self::getNewPriority($user_id);
		$query = "insert into `student_research` (`user_id`, `citation`, `priority`, `status`) value (?,?,?,IFNULL(?,0))";
		if(!$db->Execute($query, array($user_id, $details, $priority, $status))) {
			add_error("Failed to create new Research Citation.");
			application_log("error", "Unable to create a student_research record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully added new Research Citation.");
		}
	}
	
	/**
	 * Deletes the citation from the DB and resequences the following priorities
	 */
	public function delete() {
		
		$cur_priority = $this->priority;
		$user_id = $this->user_id;
		
		global $db;
		$query = "DELETE FROM `student_research` where `id`=".$db->qstr($this->id);
		if(!$db->Execute($query)) {
			add_error("Failed to remove Research Citation from database.");
			application_log("error", "Unable to delete a student_research record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed Research Citation.");
		}
		$query = "UPDATE `student_research` set `priority`=`priority`-1 where `priority` > ".$db->qstr($cur_priority)." and `user_id`=".$db->qstr($user_id);
		$db->Execute($query);
				
	}
	
	public function setStatus($status_code, $comment=null) {
		global $db;
		$query = "update `student_research` set
				 `status`=?, `comment`=? 
				 where `id`=?";
		
		if(!$db->Execute($query, array($status_code, $comment, $this->id))) {
			add_error("Failed to update Research Citation.");
			application_log("error", "Unable to update a student_research record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated Research Citation.");
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
	
	/**
	 * CAUTION: this does not affect other entries. it would be easy to create a conflict with unexpected results. Use ResearchCitations::resequence() instead.
	 * @param int $priority
	 */
	public function setPriority($priority) {
		global $db;
		$query = "update `student_research` set
				 `priority`=0 
				 where `id`=".$db->qstr($this->id);
		
		if($db->Execute($query)) {
			$this->priority = $priority;
		}
	}
	
	public function update(array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "update `student_research` set `citation`=?, `status`=?, `comment`=? where `id`=?";
		if(!$db->Execute($query, array($details, $status, $comment, $this->id))) {
			add_error("Failed to update Research Citation.");
			application_log("error", "Unable to update a student_research record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated Research Citation.");
		}
	}
}
