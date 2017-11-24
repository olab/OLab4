<?php

/**
 * 
 * Entrada [ http://www.entrada-project.org ]
 * 
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */
class Studentship implements Editable {
	private $id;
	private $user_id;
	private $title;
	private $year;
	
	function __construct($id, $user_id, $title, $year) {
		$this->id = $id;
		$this->user_id = $user_id;
		$this->title = $title;
		$this->year = $year;
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
	
	public function getTitle(){
		return $this->title;
	}
	
	public function getYear() {
		return $this->year;
	}
	
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `student_studentships` WHERE `id` = ".$db->qstr($id)." ORDER BY `year` ASC";
		$result = $db->getRow($query);
		if ($result) {
			
			$studentship =  new Studentship($result['id'], $result['user_id'], $result['title'], $result['year']);
			return $studentship;
		}
	} 
	
	public static function create(array $input_arr) {
		extract($input_arr);
		global $db;
	
		$query = "insert into `student_studentships` (`user_id`, `title`,`year`) value (?,?,?)";
		if(!$db->Execute($query, array($user_id, $title, $year))) {
			add_error("Failed to create new studentship.");
			application_log("error", "Unable to update a student_studentships record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully added new studentship.");
			$insert_id = $db->Insert_ID();
			return self::get($insert_id); 
		}
	}
	
	public function delete() {
		global $db;
		$query = "DELETE FROM `student_studentships` where `id`=".$db->qstr($this->id);
		if(!$db->Execute($query)) {
			add_error("Failed to remove studentship.");
			application_log("error", "Unable to delete a student_studentships record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed studentship.");
		}		
	}
	
	public function update(array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "update `student_studentships` set
				 `title`=?, `year`=?  
				 where `id`=?";
		if(!$db->Execute($query, array($title, $year, $this->id))) {
			add_error("Failed to update studentship.");
			application_log("error", "Unable to update a student_studentships record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated studentship.");
		}	
	}
}