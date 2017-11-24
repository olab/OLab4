<?php

class Contribution implements Approvable, AttentionRequirable, Editable {
	private $id;
	private $user_id;
	private $role;
	private $start_month;
	private $end_month;
	private $start_year;
	private $end_year;
	private $org_event;
	private $status;
	
	function __construct($id, $user_id, $role, $org_event, $start_month, $start_year, $end_month, $end_year, $comment, $status = 0) {
		$this->id = $id;
		$this->user_id = $user_id;
		$this->role = $role;
		$this->org_event = $org_event;
		$this->status = $status;
		$this->comment = $comment;
		
		$this->start_month = $start_month;
		$this->start_year = $start_year;
		$this->end_month = $end_month;
		$this->end_year = $end_year;
	}
	
	public static function fromArray(array $arr) {
		return new self($arr['id'], $arr['user_id'], $arr['role'], $arr['org_event'], $arr['start_month'], $arr['start_year'], $arr['end_month'], $arr['end_year'], $arr['comment'], $arr['status']);
	
	}
	
	public function getComment(){
		return $this->comment;
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
	
	public function getRole(){
		return $this->role;
	}
	
	public function getOrgEvent() {
		return $this->org_event;
	}
	
	public function getStartMonth(){
		return $this->start_month;
	}
	
	public function getStartYear(){
		return $this->start_year;
	}
	
	public function getEndMonth(){
		return $this->end_month;
	}
	
	public function getEndYear(){
		return $this->end_year;
	}
	
	public function getStartDate() {
		return array(
			"m" => $this->start_month,
			"y" => $this->start_year
		);
	}
	
	public function getEndDate() {
		return array(
			"m" => $this->end_month,
			"y" => $this->end_year
		);
	}
	
	public function getPeriod() {
		return formatDateRange($this->getStartDate(), $this->getEndDate()); 
	}
	
	public function isApproved() {
		return ($this->status == 1);
	}
	
	/**
	 * Requires attention if not approved, unless rejected
	 * @see www-root/core/library/Models/AttentionRequirable#isAttentionRequired()
	 */
	public function isAttentionRequired() {
		return !$this->isApproved() && !$this->isRejected();
	}
	
	public function isRejected() {
		return ($this->status == -1);
	}
		
	/**
	 * 
	 * @param int $id
	 * @return Contribution
	 */
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `student_contributions` WHERE `id` = ".$db->qstr($id);
		$result = $db->getRow($query);
		if ($result) {
			$contribution = Contribution::fromArray($result);
			return $contribution;
		}
	} 
	
	public static function create(array $input_arr) {
		extract($input_arr);
		global $db;
        if (($start_year <= $end_year) && ($start_month < $end_month)) {
            $query = "insert into `student_contributions` (`user_id`, `role`,`org_event`,`start_month`, `start_year`, `end_month`,`end_year`, `status`) 
				value (?,?,?,?,?,?,?,IFNULL(?,0))";
            if(!$db->Execute($query, array($user_id, $role, $org_event, $start_month, $start_year, $end_month, $end_year, status))) {
                add_error("Failed to create new contribution.");
                application_log("error", "Unable to update a student_contributions record. Database said: ".$db->ErrorMsg());
            } else {
                add_success("Successfully added new contribution.");
            }
        } else {
            add_error("Failed to create new contribution. Invalid dates");
        }
	}
	
	public function delete() {
		global $db;
		$query = "DELETE FROM `student_contributions` where `id`=".$db->qstr($this->id);
		if(!$db->Execute($query)) {
			add_error("Failed to remove contribution from database.");
			application_log("error", "Unable to delete a student_contributions record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed contribution.");
		}		
	}
	
	private function setStatus($status_code, $comment=null) {
		global $db;
		$query = "update `student_contributions` set
				 `status`=?, `comment`=?  
				 where `id`=?";
		
		if(!$db->Execute($query, array($status_code, $comment, $this->id))) {
			add_error("Failed to update contribution.");
			application_log("error", "Unable to update a student_contributions record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated contribution.");
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
	
	public function getStatus() {
		return $this->status;
	}
	
	public function update (array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "update `student_contributions` set
				 `role`=?, `org_event`=?,`start_month`=?, `start_year`=?, `end_month`=?,`end_year`=?,`status`=?, `comment`=?  
				 where `id`=?";
		if(!$db->Execute($query, array($role, $org_event, $start_month, $start_year, $end_month, $end_year,$status, $comment, $this->id))) {
			add_error("Failed to update contribution.");
			application_log("error", "Unable to update a student_contributions record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated contribution.");
		}
	}
}