<?php

require_once("AbstractStudentDetails.class.php");

class DisciplinaryAction extends AbstractStudentDetails {
	
	function __construct($user, $id, $details) {
		$this->user = $user;
		$this->id = $id;
		$this->details = $details;
	}
	
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `student_disciplinary_actions` where `id`=".$db->qstr($id);
		$result	= $db->GetRow($query);
		if ($result) {
			$user = User::fetchRowByID($result['user_id']);
			if ($user) {
				$da = new DisciplinaryAction($user, $result['id'], $result['action_details']);
				return $da;
			}
		}
	}
	
	public static function create($user_id, $details) {
		global $db;

		$query = "insert into `student_disciplinary_actions` (`user_id`, `action_details`) value (".$db->qstr($user_id).", ".$db->qstr($details).")";
		
		if(!$db->Execute($query)) {
			add_error("Failed to create new Disciplinary Action.");
			application_log("error", "Unable to update a student_disciplinary_actions record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully added new Disciplinary Action.");
			$insert_id = $db->Insert_ID();
			return self::get($insert_id); 
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `student_disciplinary_actions` where `id`=".$db->qstr($this->id);
		if(!$db->Execute($query)) {
			add_error("Failed to remove disciplinary action from database.");
			application_log("error", "Unable to delete a student_disciplinary_actions record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed disciplinary action.");
		}	
	}
	
}