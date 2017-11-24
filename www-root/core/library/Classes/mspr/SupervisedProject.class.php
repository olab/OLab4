<?php

require_once("Classes/utility/Approvable.interface.php");
require_once("Classes/utility/AttentionRequirable.interface.php");
require_once("Classes/utility/Editable.interface.php");

abstract class SupervisedProject implements Approvable,AttentionRequirable, Editable {
	private $user_id;
	private $location;
	private $organization;
	private $title;
	private $supervisor;
	private $comment;
	
	function __construct($user_id, $title, $organization, $location, $supervisor, $comment, $status=0) {
		$this->user_id = $user_id;
		$this->location = $location;
		$this->title = $title;
		$this->organization = $organization;
		$this->supervisor = $supervisor;
		$this->comment = $comment;
		$this->status = $status;
	}
	
	public function getID() {
		return $this->user_id;
	}
	
	public function getUserID() {
		return $this->user_id;	
	}
	
	public function getUser() {
		return User::fetchRowByID($this->user_id);
	}

	public function getLocation () {
		return $this->location;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getSupervisor() {
		return $this->supervisor;
	}
	
	public function getOrganization() {
		return $this->organization;
	}
	
	public function getDetails() {
		$elements = array();
		$elements[] = '"'.$this->title.'"';
		$elements[] = $this->organization;
		$elements[] = $this->location;
		$elements[] = 'Supervisor: '.$this->supervisor;
		
		$details = implode("\n", $elements);
		return $details;
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
	
	public function getComment() {
		return $this->comment;
	}
}