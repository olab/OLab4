<?php

class ClerkshipRotation {
	
	protected $completed = false;
	protected $event_start;
	protected $event_finish;
	protected $title;
	protected $user_id;
	
	function __construct($user_id, $title, $event_start, $event_finish, $completed = false) {
		$this->user_id = $user_id;
		$this->title = $title;
		$this->event_start = $event_start;
		$this->event_finish = $event_finish;
		$this->completed = $completed;
	}
	
	public function getStart() {
		return $this->event_start;
	} 
	
	public function getFinish() {
		return $this->event_finish;	
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function isCompleted() {
		return (bool) ($this->completed);
	}
	
	public function getDetails() {
		return $this->title;
	}
	
	public function getPeriod() {
		return date("F j, Y", $this->event_start) . " - " . date("F j, Y", $this->event_finish); 
	}
	
	public function getUser() {
		return User::fetchRowByID($this->user_id);
	}
}