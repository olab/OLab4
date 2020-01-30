<?php

require_once("InternationalActivity.class.php");
require_once("Classes/utility/Collection.class.php");

class InternationalActivities extends Collection {
	public static function get(User $user) {
		global $db;
		$user_id = $user->getID();
		$query		= "SELECT *, UNIX_TIMESTAMP(`end`) as `end`, UNIX_TIMESTAMP(`start`) as `start`  FROM `student_international_activities` WHERE `student_id` = ".$db->qstr($user_id)." ORDER BY `start` ASC";
		$results = $db->getAll($query);
		$int_acts = array();
		if ($results) {
			foreach ($results as $result) {
				$int_act =  new InternationalActivity($result['id'], $result['student_id'], $result['title'], $result['site'], $result['location'], $result['start'], $result['end']);
				$int_acts[] = $int_act;
			}
		} 
		return new self($int_acts);
	}
}