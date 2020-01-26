<?php
abstract class AbstractStudentDetails {
	protected $id;
	protected $user_id;
	protected $details;
		
	public abstract function delete();
	
	public function getID() {
		return $this->id;
	}
	
	public function getUser() {
		return User::fetchRowByID($this->user_id);
	}
	
	public function getDetails() {
		return $this->details;
	}
	
}