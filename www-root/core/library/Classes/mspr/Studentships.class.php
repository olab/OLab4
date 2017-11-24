<?php

require_once("Studentship.class.php");
require_once("Classes/utility/Collection.class.php");

class Studentships extends Collection {
	public static function get(User $user) {
		global $db;
		
		$studentships = array();
		$user_id = $user->getID();
		$query = "SELECT * FROM `student_studentships` WHERE `user_id` = ".$db->qstr($user_id)." ORDER BY `year` ASC";
		$results = $db->getAll($query);
		if ($results) {
			foreach ($results as $result) {
				$studentship = new Studentship($result['id'], $result['user_id'], $result['title'], $result['year']);
				$studentships[] = $studentship;
			}
		}
		return new self($studentships);
	}
}