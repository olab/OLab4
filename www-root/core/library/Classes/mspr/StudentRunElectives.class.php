<?php

require_once("StudentRunElective.class.php");
require_once("Classes/utility/Collection.class.php");

class StudentRunElectives extends Collection {
	public static function get(User $user) {
		global $db;
		$user_id = $user->getID();
		$query = "SELECT * FROM `student_student_run_electives` WHERE `user_id` = ".$db->qstr($user_id)." ORDER BY `start_year` ASC, `start_month` ASC";
		$results = $db->getAll($query);
		$sres = array();
		if ($results) {
			foreach ($results as $result) {
				$sre =  new StudentRunElective($result['id'], $result['user_id'], $result['group_name'], $result['university'], $result['location'], $result['start_month'], $result['start_year'], $result['end_month'], $result['end_year']);
				$sres[] = $sre;
			}
		} 
		return new self($sres);
	}
}