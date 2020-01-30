<?php

require_once("Classes/utility/Collection.class.php");
require_once("LeaveOfAbsence.class.php");

class LeavesOfAbsence extends Collection {
	public static function get(User $user) {
		global $db;
		$user_id = $user->getID();
		$query = "SELECT * FROM `student_leaves_of_absence` WHERE `user_id` = ".$db->qstr($user_id);
		$results = $db->getAll($query);
		$frs = array();
		if ($results) {
			foreach ($results as $result) {
				$fr =  LeaveOfAbsence::fromArray($result);
				$frs[] = $fr;
			}
			return new self($frs);
		}
	}
}